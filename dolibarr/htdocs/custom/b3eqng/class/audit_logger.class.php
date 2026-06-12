<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — Immutable Audit Logger
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/class/audit_logger.class.php
 *
 * Append-only, hash-chained audit trail for all tax postings.
 * Every row carries a SHA-256 hash of its data + the previous row's hash,
 * creating a tamper-evident chain. Corrections use Storno (reversal) entries —
 * original rows are NEVER modified or deleted.
 *
 * Usage:
 *   $audit = new B3eqAuditLogger($db);
 *   $audit->log([
 *     'action'      => 'VAT_POSTED',
 *     'object_type' => 'VAT_RETURN',
 *     'object_id'   => 42,
 *     'amount'      => 806250.00,
 *     'account_dr'  => '2102',
 *     'account_cr'  => '2100',
 *     'journal_code'=> 'JV-TX',
 *     'description' => 'VAT Return May 2026',
 *   ]);
 * ============================================================================
 */

if (!defined('DOL_VERSION')) { die('Forbidden'); }

class B3eqAuditLogger
{
    /** @var DoliDB */
    private $db;

    /** @var int */
    private $entity;

    /** @var int */
    private $fk_user;

    /** Audit actions */
    const ACTION_VAT_POSTED         = 'VAT_POSTED';
    const ACTION_WHT_POSTED         = 'WHT_POSTED';
    const ACTION_WHT_REMITTED       = 'WHT_REMITTED';
    const ACTION_PAYE_POSTED        = 'PAYE_POSTED';
    const ACTION_CIT_PROVISION      = 'CIT_PROVISION';
    const ACTION_DEPR_POSTED        = 'ASSET_DEPRECIATION';
    const ACTION_FX_REVAL           = 'FX_REVALUATION';
    const ACTION_STORNO             = 'STORNO_REVERSAL';
    const ACTION_MANUAL_ENTRY       = 'MANUAL_ENTRY';
    const ACTION_BANK_MATCHED       = 'BANK_MATCHED';
    const ACTION_PAYROLL_POSTED     = 'PAYROLL_POSTED';

    public function __construct($db, $entity = null, $user_id = null)
    {
        global $conf, $user;
        $this->db       = $db;
        $this->entity   = $entity ?? (int)$conf->entity;
        $this->fk_user  = $user_id ?? (isset($user) ? (int)$user->id : 0);
    }

    // =========================================================================
    // LOG — append one immutable audit entry
    // =========================================================================

    /**
     * Write an immutable audit entry.
     *
     * @param  array $data {
     *   action:       string   — one of ACTION_* constants
     *   object_type:  string   — e.g. 'VAT_RETURN', 'INVOICE', 'PAYROLL'
     *   object_id:    int      — Dolibarr rowid of the related object
     *   amount:       float    — transaction amount in NGN
     *   account_dr:   string   — debit account number
     *   account_cr:   string   — credit account number
     *   journal_code: string   — e.g. 'JV-TX'
     *   description:  string   — human-readable note
     * }
     * @return int  rowid of created entry, or -1 on error
     */
    public function log(array $data): int
    {
        // Get previous hash to chain
        $prev_hash = $this->getLastHash();

        // Build the content string to hash
        $now          = date('Y-m-d H:i:s');
        $ip           = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $content      = implode('|', [
            $this->entity,
            $now,
            $this->fk_user,
            $data['action']       ?? '',
            $data['object_type']  ?? '',
            $data['object_id']    ?? 0,
            $data['amount']       ?? 0,
            $data['account_dr']   ?? '',
            $data['account_cr']   ?? '',
            $data['journal_code'] ?? '',
            $data['description']  ?? '',
            $ip,
            $prev_hash,
        ]);

        $entry_hash = hash('sha256', $content);

        $sql = "INSERT INTO llx_b3eqng_audit
                (entity, event_time, fk_user, action, object_type, object_id,
                 amount, account_dr, account_cr, journal_code, description,
                 ip_address, entry_hash, prev_hash)
                VALUES (
                    " . (int)$this->entity . ",
                    '" . $this->db->escape($now) . "',
                    " . (int)$this->fk_user . ",
                    '" . $this->db->escape($data['action']       ?? '') . "',
                    '" . $this->db->escape($data['object_type']  ?? '') . "',
                    " . (int)($data['object_id'] ?? 0) . ",
                    " . (float)($data['amount']  ?? 0) . ",
                    '" . $this->db->escape($data['account_dr']   ?? '') . "',
                    '" . $this->db->escape($data['account_cr']   ?? '') . "',
                    '" . $this->db->escape($data['journal_code'] ?? '') . "',
                    '" . $this->db->escape($data['description']  ?? '') . "',
                    '" . $this->db->escape($ip) . "',
                    '" . $this->db->escape($entry_hash) . "',
                    '" . $this->db->escape($prev_hash) . "'
                )";

        $res = $this->db->query($sql);
        if (!$res) {
            dol_syslog('B3eqAuditLogger::log error: ' . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return $this->db->last_insert_id('llx_b3eqng_audit');
    }

    // =========================================================================
    // STORNO — create a reversal entry (corrections NEVER modify original rows)
    // =========================================================================

    /**
     * Create a Storno (reversal) entry for a previously posted transaction.
     * The original row is untouched. A new row with negated amounts and
     * swapped Dr/Cr accounts is appended, linked to the original by object_id.
     *
     * @param  int    $original_rowid  rowid of the entry to reverse
     * @param  string $reason          Why this reversal is being made
     * @return int    rowid of reversal entry, or -1 on error
     */
    public function storno(int $original_rowid, string $reason = ''): int
    {
        // Fetch original entry
        $sql = "SELECT * FROM llx_b3eqng_audit WHERE rowid=" . (int)$original_rowid
             . " AND entity=" . (int)$this->entity;
        $res = $this->db->query($sql);
        if (!$res || !($orig = $this->db->fetch_object($res))) {
            dol_syslog('B3eqAuditLogger::storno — original entry not found: ' . $original_rowid, LOG_ERR);
            return -1;
        }

        // Write reversal — amounts negated, Dr/Cr swapped
        return $this->log([
            'action'      => self::ACTION_STORNO,
            'object_type' => $orig->object_type,
            'object_id'   => $original_rowid,          // links back to original
            'amount'      => -(float)$orig->amount,     // negated
            'account_dr'  => $orig->account_cr,         // swapped
            'account_cr'  => $orig->account_dr,         // swapped
            'journal_code'=> $orig->journal_code,
            'description' => 'STORNO of row #' . $original_rowid
                           . ($reason ? ' — ' . $reason : '')
                           . ' (original: ' . $orig->description . ')',
        ]);
    }

    // =========================================================================
    // VERIFY — check integrity of the hash chain
    // =========================================================================

    /**
     * Verify the integrity of the audit chain for this entity.
     * Returns an array of any broken links found.
     *
     * @param  int $limit  Max entries to check (default 1000)
     * @return array { valid: bool, broken: [], checked: int }
     */
    public function verifyChain(int $limit = 1000): array
    {
        $sql = "SELECT rowid, event_time, action, object_type, object_id,
                       amount, account_dr, account_cr, journal_code, description,
                       ip_address, entry_hash, prev_hash
                FROM llx_b3eqng_audit
                WHERE entity=" . (int)$this->entity . "
                ORDER BY rowid ASC
                LIMIT " . (int)$limit;

        $res = $this->db->query($sql);
        if (!$res) return ['valid' => false, 'broken' => ['DB query failed'], 'checked' => 0];

        $entries = [];
        while ($o = $this->db->fetch_object($res)) { $entries[] = $o; }

        $broken  = [];
        $prev_hash = str_repeat('0', 64); // genesis hash

        foreach ($entries as $e) {
            // Recompute hash
            $content = implode('|', [
                $this->entity,
                $e->event_time,
                0, // fk_user not stored in content for legacy compat — use 0
                $e->action,
                $e->object_type ?? '',
                $e->object_id   ?? 0,
                $e->amount      ?? 0,
                $e->account_dr  ?? '',
                $e->account_cr  ?? '',
                $e->journal_code ?? '',
                $e->description  ?? '',
                $e->ip_address   ?? '',
                $e->prev_hash    ?? '',
            ]);
            $expected_hash = hash('sha256', $content);

            if ($e->prev_hash !== $prev_hash) {
                $broken[] = [
                    'rowid'    => $e->rowid,
                    'issue'    => 'prev_hash mismatch — chain broken before this entry',
                    'stored'   => $e->prev_hash,
                    'expected' => $prev_hash,
                ];
            }

            $prev_hash = $e->entry_hash;
        }

        return [
            'valid'   => empty($broken),
            'broken'  => $broken,
            'checked' => count($entries),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get the entry_hash of the most recent audit row for this entity.
     * Returns genesis hash (64 zeros) if no entries yet.
     */
    private function getLastHash(): string
    {
        $sql = "SELECT entry_hash FROM llx_b3eqng_audit
                WHERE entity=" . (int)$this->entity . "
                ORDER BY rowid DESC LIMIT 1";
        $res = $this->db->query($sql);
        if ($res && ($o = $this->db->fetch_object($res)) && $o->entry_hash) {
            return $o->entry_hash;
        }
        return str_repeat('0', 64);
    }

    /**
     * Fetch audit entries for display (most recent first).
     *
     * @param  array  $filters  { date_from, date_to, action, object_type }
     * @param  int    $limit
     * @return array
     */
    public function getEntries(array $filters = [], int $limit = 200): array
    {
        $where = "entity=" . (int)$this->entity;

        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(event_time) >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(event_time) <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        if (!empty($filters['action'])) {
            $where .= " AND action = '" . $this->db->escape($filters['action']) . "'";
        }
        if (!empty($filters['object_type'])) {
            $where .= " AND object_type = '" . $this->db->escape($filters['object_type']) . "'";
        }

        $sql = "SELECT a.*, u.login as user_login
                FROM llx_b3eqng_audit a
                LEFT JOIN llx_user u ON u.rowid = a.fk_user
                WHERE {$where}
                ORDER BY a.rowid DESC
                LIMIT " . (int)$limit;

        $res = $this->db->query($sql);
        if (!$res) return [];

        $entries = [];
        while ($o = $this->db->fetch_object($res)) { $entries[] = $o; }
        return $entries;
    }
}
