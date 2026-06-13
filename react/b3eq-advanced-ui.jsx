import { useState, useEffect, useRef } from "react";

// ─── DATA ─────────────────────────────────────────────────────────────────────
const COA_TREE = [
  { code:"1000", label:"Cash and Cash Equivalents",       type:"ASSET",  sub:"CURRENTASSET",    balance: 12450000 },
  { code:"1002", label:"Current Account – CBN/Banks",     type:"ASSET",  sub:"CURRENTASSET",    balance: 8200000  },
  { code:"1100", label:"Accounts Receivable",             type:"ASSET",  sub:"CURRENTASSET",    balance: 5350000  },
  { code:"1101", label:"VAT Recoverable (Input VAT)",     type:"ASSET",  sub:"CURRENTASSET",    balance: 403125   },
  { code:"1102", label:"WHT Credit Receivable",           type:"ASSET",  sub:"CURRENTASSET",    balance: 220000   },
  { code:"1200", label:"Inventory – Finished Goods",      type:"ASSET",  sub:"CURRENTASSET",    balance: 3100000  },
  { code:"1300", label:"Property Plant & Equipment",      type:"ASSET",  sub:"NONCURRENTASSET", balance: 15000000 },
  { code:"1301", label:"Accum. Depreciation – PPE",       type:"ASSET",  sub:"NONCURRENTASSET", balance: -4500000 },
  { code:"2000", label:"Accounts Payable",                type:"LIABILITIES","sub":"CURRENTLIABILITIES", balance:-3200000},
  { code:"2100", label:"VAT Payable – Output 7.5%",       type:"LIABILITIES","sub":"CURRENTLIABILITIES", balance:-806250 },
  { code:"2113", label:"WHT Payable – Contracts 5%",      type:"LIABILITIES","sub":"CURRENTLIABILITIES", balance:-175000 },
  { code:"2120", label:"PAYE Tax Payable",                type:"LIABILITIES","sub":"CURRENTLIABILITIES", balance:-340000 },
  { code:"2121", label:"Pension Payable – Employer",      type:"LIABILITIES","sub":"CURRENTLIABILITIES", balance:-120000 },
  { code:"2130", label:"CIT Payable",                     type:"LIABILITIES","sub":"CURRENTLIABILITIES", balance:-1500000},
  { code:"3002", label:"Retained Earnings",               type:"EQUITY", sub:"EQUITY",          balance:-9200000 },
  { code:"4000", label:"Sales Revenue – Goods",           type:"INCOME", sub:"OPERATIONALREVENUE",balance:-10750000},
  { code:"4001", label:"Sales Revenue – Services",        type:"INCOME", sub:"OPERATIONALREVENUE",balance:-5400000 },
  { code:"6000", label:"Staff Salaries & Wages",          type:"EXPENSE","sub":"EXPENSE",        balance: 4800000  },
  { code:"6002", label:"Employer Pension (10%)",          type:"EXPENSE","sub":"EXPENSE",        balance: 480000   },
  { code:"6010", label:"Rent & Lease",                    type:"EXPENSE","sub":"EXPENSE",        balance: 600000   },
  { code:"6020", label:"Professional Fees",               type:"EXPENSE","sub":"EXPENSE",        balance: 350000   },
  { code:"6030", label:"Depreciation – PPE",              type:"EXPENSE","sub":"EXPENSE",        balance: 375000   },
  { code:"7000", label:"CIT Expense",                     type:"EXPENSE","sub":"EXPENSE",        balance: 450000   },
];

const UNMATCHED = [
  { id:"TXN-001", date:"2026-06-01", narration:"TRSF FROM DANGOTE",        amount: 2500000,  type:"CR", account:"1002", status:"UNMATCHED" },
  { id:"TXN-002", date:"2026-06-02", narration:"NNPC FUEL SUPPLY PMT",     amount:-850000,   type:"DR", account:"1002", status:"UNMATCHED" },
  { id:"TXN-003", date:"2026-06-03", narration:"FIRS VAT REMITTANCE",      amount:-806250,   type:"DR", account:"2100", status:"UNMATCHED" },
  { id:"TXN-004", date:"2026-06-04", narration:"PAYROLL JUNE 2026",        amount:-4800000,  type:"DR", account:"6000", status:"UNMATCHED" },
  { id:"TXN-005", date:"2026-06-05", narration:"KOLA VENTURES INV-0041",   amount: 1072500,  type:"CR", account:"1100", status:"UNMATCHED" },
  { id:"TXN-006", date:"2026-06-06", narration:"RENT Q2 VICTORIA ISLAND",  amount:-600000,   type:"DR", account:"6010", status:"UNMATCHED" },
  { id:"TXN-007", date:"2026-06-07", narration:"PENSION JUNE EMPLOYER",    amount:-120000,   type:"DR", account:"2121", status:"UNMATCHED" },
  { id:"TXN-008", date:"2026-06-08", narration:"SALES INV-0088 NESTLE",    amount: 3225000,  type:"CR", account:"4000", status:"UNMATCHED" },
];

const TYPE_COLORS = { ASSET:"#22d3ee", LIABILITIES:"#f87171", EQUITY:"#a78bfa", INCOME:"#34d399", EXPENSE:"#fb923c" };
const fmt = n => "₦" + Math.abs(n).toLocaleString("en-NG", { minimumFractionDigits:2, maximumFractionDigits:2 });

// ─── DRILLDOWN COA TREE ───────────────────────────────────────────────────────
function COATree() {
  const [expanded, setExpanded] = useState({});
  const [selected, setSelected] = useState(null);
  const [search, setSearch]     = useState("");

  const groups = {};
  COA_TREE
    .filter(a => !search || a.code.includes(search) || a.label.toLowerCase().includes(search.toLowerCase()))
    .forEach(a => {
      if (!groups[a.type]) groups[a.type] = [];
      groups[a.type].push(a);
    });

  const groupTotal = (accounts) => accounts.reduce((s, a) => s + a.balance, 0);

  return (
    <div style={{ fontFamily:"'DM Sans',sans-serif" }}>
      <input value={search} onChange={e => setSearch(e.target.value)}
             placeholder="Search account…"
             style={{ width:"100%", background:"rgba(255,255,255,0.05)", border:"1px solid #2a1a2e",
                      borderRadius:8, padding:"8px 12px", color:"#f0e8ff", fontSize:13,
                      outline:"none", marginBottom:16, boxSizing:"border-box" }} />

      {Object.entries(groups).map(([type, accounts]) => {
        const total = groupTotal(accounts);
        const isOpen = expanded[type];
        const col = TYPE_COLORS[type] || "#fff";
        return (
          <div key={type} style={{ marginBottom:8 }}>
            {/* Group header */}
            <div onClick={() => setExpanded(e => ({ ...e, [type]: !e[type] }))}
                 style={{ display:"flex", alignItems:"center", justifyContent:"space-between",
                          background: isOpen ? `${col}18` : "rgba(255,255,255,0.03)",
                          border:`1px solid ${col}33`, borderRadius:8, padding:"10px 14px",
                          cursor:"pointer", userSelect:"none" }}>
              <div style={{ display:"flex", alignItems:"center", gap:10 }}>
                <span style={{ color:col, fontWeight:800, fontSize:13, letterSpacing:".04em" }}>{type}</span>
                <span style={{ color:"#6b5f7a", fontSize:11 }}>{accounts.length} accounts</span>
              </div>
              <div style={{ display:"flex", alignItems:"center", gap:12 }}>
                <span style={{ fontFamily:"monospace", fontWeight:700, fontSize:13,
                               color: total >= 0 ? "#34d399" : "#f87171" }}>{fmt(total)}</span>
                <span style={{ color:col }}>{isOpen ? "▲" : "▼"}</span>
              </div>
            </div>

            {/* Account rows */}
            {isOpen && (
              <div style={{ border:`1px solid ${col}22`, borderTop:"none",
                            borderRadius:"0 0 8px 8px", overflow:"hidden" }}>
                {accounts.map((a, i) => (
                  <div key={a.code}
                       onClick={() => setSelected(selected?.code === a.code ? null : a)}
                       style={{ display:"flex", alignItems:"center", justifyContent:"space-between",
                                padding:"8px 14px 8px 28px",
                                background: selected?.code === a.code ? `${col}15`
                                          : i%2===0 ? "transparent" : "rgba(255,255,255,0.015)",
                                borderBottom:"1px solid rgba(255,255,255,0.04)",
                                cursor:"pointer" }}>
                    <div style={{ display:"flex", alignItems:"center", gap:10 }}>
                      <span style={{ fontFamily:"monospace", color:"#22d3ee", fontSize:12, width:45 }}>{a.code}</span>
                      <span style={{ color:"#f0e8ff", fontSize:12.5 }}>{a.label}</span>
                    </div>
                    <span style={{ fontFamily:"monospace", fontSize:12, fontWeight:600,
                                   color: a.balance >= 0 ? "#34d399" : "#f87171" }}>
                      {fmt(a.balance)}
                    </span>
                  </div>
                ))}

                {/* Drilldown panel for selected account */}
                {selected && accounts.find(a => a.code === selected.code) && (
                  <div style={{ background:"rgba(0,0,0,0.3)", padding:"14px 18px",
                                borderTop:`1px solid ${col}33` }}>
                    <div style={{ fontFamily:"'Syne',sans-serif", fontWeight:800, fontSize:14,
                                  color:"#f0e8ff", marginBottom:12 }}>
                      {selected.code} — {selected.label}
                    </div>
                    <div style={{ display:"grid", gridTemplateColumns:"1fr 1fr 1fr", gap:10, marginBottom:14 }}>
                      {[
                        ["Balance", fmt(selected.balance), selected.balance>=0?"#34d399":"#f87171"],
                        ["Type", selected.type, col],
                        ["Sub-type", selected.sub, "#6b5f7a"],
                      ].map(([lbl, val, c]) => (
                        <div key={lbl} style={{ background:"rgba(255,255,255,0.05)", borderRadius:6, padding:"8px 10px" }}>
                          <div style={{ fontSize:10, color:"#6b5f7a", textTransform:"uppercase", letterSpacing:".06em", marginBottom:3 }}>{lbl}</div>
                          <div style={{ color:c, fontWeight:700, fontSize:12 }}>{val}</div>
                        </div>
                      ))}
                    </div>
                    {/* Simulated ledger lines */}
                    <div style={{ fontSize:11, color:"#6b5f7a", marginBottom:8, textTransform:"uppercase", letterSpacing:".06em" }}>
                      Recent entries (simulated)
                    </div>
                    {[
                      { date:"2026-06-08", desc:"Sales Invoice INV-0088", dr: selected.balance > 0 ? Math.abs(selected.balance)*0.3 : 0, cr: selected.balance < 0 ? Math.abs(selected.balance)*0.3 : 0 },
                      { date:"2026-06-04", desc:"Month-end allocation", dr: selected.balance > 0 ? Math.abs(selected.balance)*0.5 : 0, cr: selected.balance < 0 ? Math.abs(selected.balance)*0.5 : 0 },
                      { date:"2026-06-01", desc:"Opening balance", dr: selected.balance > 0 ? Math.abs(selected.balance)*0.2 : 0, cr: selected.balance < 0 ? Math.abs(selected.balance)*0.2 : 0 },
                    ].map((line, i) => (
                      <div key={i} style={{ display:"flex", gap:8, fontSize:11, padding:"4px 0",
                                            borderBottom:"1px solid rgba(255,255,255,0.05)", color:"#94a3b8" }}>
                        <span style={{ color:"#6b5f7a", width:75 }}>{line.date}</span>
                        <span style={{ flex:1 }}>{line.desc}</span>
                        <span style={{ color:"#34d399", width:90, textAlign:"right", fontFamily:"monospace" }}>{line.dr > 0 ? fmt(line.dr) : ""}</span>
                        <span style={{ color:"#f87171", width:90, textAlign:"right", fontFamily:"monospace" }}>{line.cr > 0 ? fmt(line.cr) : ""}</span>
                      </div>
                    ))}
                    <div style={{ marginTop:10, fontSize:11, color:"#475569" }}>
                      In production: connects to Dolibarr <code style={{ color:"#22d3ee" }}>api/b3eqng/coa/{selected.code}/ledger</code>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

// ─── RECONCILIATION BOARD ─────────────────────────────────────────────────────
function ReconciliationBoard() {
  const [transactions, setTransactions] = useState(UNMATCHED);
  const [matched, setMatched]           = useState([]);
  const [dragging, setDragging]         = useState(null);
  const [hovering, setHovering]         = useState(null);

  const pending   = transactions.filter(t => t.status === "UNMATCHED");
  const confirmed = matched;

  const autoMatch = () => {
    const autoMatched = pending.map(t => ({
      ...t,
      status:           "MATCHED",
      matched_account:  t.account,
      confidence:       t.narration.toLowerCase().includes("vat") || t.narration.toLowerCase().includes("firs") ? "HIGH"
                        : t.narration.toLowerCase().includes("payroll") ? "HIGH"
                        : t.narration.toLowerCase().includes("rent") ? "HIGH"
                        : "MEDIUM",
    }));
    setMatched(prev => [...prev, ...autoMatched]);
    setTransactions(prev => prev.map(t => ({ ...t, status:"MATCHED" })));
  };

  const unmatch = (id) => {
    const item = matched.find(m => m.id === id);
    if (!item) return;
    setMatched(prev => prev.filter(m => m.id !== id));
    setTransactions(prev => prev.map(t => t.id === id ? { ...t, status:"UNMATCHED" } : t));
  };

  return (
    <div>
      <div style={{ display:"flex", alignItems:"center", justifyContent:"space-between", marginBottom:16 }}>
        <div style={{ fontSize:13, color:"#6b5f7a" }}>
          {pending.length} unmatched &nbsp;·&nbsp; {confirmed.length} reconciled
        </div>
        <button onClick={autoMatch}
                disabled={pending.length === 0}
                style={{ background:"linear-gradient(135deg,#e4001b,#ff4d1a)", color:"#fff",
                         border:"none", borderRadius:8, padding:"8px 16px", cursor:"pointer",
                         fontSize:12, fontWeight:800, opacity: pending.length===0 ? 0.4 : 1 }}>
          ⚡ Auto-Match All
        </button>
      </div>

      {/* Unmatched transactions */}
      {pending.length > 0 && (
        <div style={{ marginBottom:20 }}>
          <div style={{ fontSize:11, color:"#6b5f7a", textTransform:"uppercase",
                        letterSpacing:".07em", marginBottom:8 }}>Unmatched Bank Transactions</div>
          {pending.map(txn => (
            <div key={txn.id}
                 draggable
                 onDragStart={() => setDragging(txn.id)}
                 onDragEnd={() => setDragging(null)}
                 style={{ display:"flex", alignItems:"center", gap:10,
                          background:"rgba(255,255,255,0.04)", border:"1px solid #2a1a2e",
                          borderLeft: `3px solid ${txn.amount>0?"#34d399":"#f87171"}`,
                          borderRadius:8, padding:"10px 14px", marginBottom:8,
                          cursor:"grab", opacity: dragging===txn.id ? 0.5 : 1,
                          transition:"all 0.15s" }}>
              <span style={{ color:"#6b5f7a", fontSize:11, width:85 }}>{txn.date}</span>
              <span style={{ flex:1, fontSize:12.5, color:"#f0e8ff" }}>{txn.narration}</span>
              <span style={{ fontFamily:"monospace", fontSize:13, fontWeight:700,
                             color: txn.amount>0 ? "#34d399" : "#f87171", width:120, textAlign:"right" }}>
                {txn.amount>0?"+":""}{fmt(txn.amount)}
              </span>
              <span style={{ fontFamily:"monospace", fontSize:11, color:"#22d3ee",
                             background:"rgba(34,211,238,0.1)", borderRadius:4, padding:"2px 6px" }}>
                {txn.account}
              </span>
              <button onClick={() => {
                        setMatched(prev => [...prev, { ...txn, status:"MATCHED", matched_account:txn.account, confidence:"MANUAL" }]);
                        setTransactions(prev => prev.map(t => t.id===txn.id ? {...t, status:"MATCHED"} : t));
                      }}
                      style={{ background:"rgba(52,211,153,0.15)", border:"1px solid rgba(52,211,153,0.4)",
                               color:"#34d399", borderRadius:6, padding:"4px 10px", cursor:"pointer",
                               fontSize:11, fontWeight:700 }}>
                ✓ Match
              </button>
            </div>
          ))}
        </div>
      )}

      {pending.length === 0 && confirmed.length === 0 && (
        <div style={{ textAlign:"center", padding:"40px 24px", color:"#6b5f7a", fontSize:13 }}>
          All transactions reconciled. Import new bank feed to continue.
        </div>
      )}

      {/* Matched transactions */}
      {confirmed.length > 0 && (
        <div>
          <div style={{ fontSize:11, color:"#34d399", textTransform:"uppercase",
                        letterSpacing:".07em", marginBottom:8 }}>
            ✓ Reconciled ({confirmed.length})
          </div>
          {confirmed.map(txn => (
            <div key={txn.id}
                 style={{ display:"flex", alignItems:"center", gap:10,
                          background:"rgba(52,211,153,0.04)", border:"1px solid rgba(52,211,153,0.2)",
                          borderRadius:8, padding:"8px 14px", marginBottom:6, opacity:0.85 }}>
              <span style={{ color:"#6b5f7a", fontSize:11, width:85 }}>{txn.date}</span>
              <span style={{ flex:1, fontSize:12, color:"#94a3b8" }}>{txn.narration}</span>
              <span style={{ fontFamily:"monospace", fontSize:12, fontWeight:600,
                             color: txn.amount>0 ? "#34d399" : "#f87171", width:120, textAlign:"right" }}>
                {txn.amount>0?"+":""}{fmt(txn.amount)}
              </span>
              <span style={{ fontFamily:"monospace", fontSize:11, color:"#22d3ee",
                             background:"rgba(34,211,238,0.1)", borderRadius:4, padding:"2px 6px" }}>
                {txn.matched_account}
              </span>
              <span style={{ fontSize:10, fontWeight:700, padding:"2px 7px", borderRadius:4,
                             background: txn.confidence==="HIGH" ? "rgba(52,211,153,0.15)" : "rgba(251,146,60,0.15)",
                             color: txn.confidence==="HIGH" ? "#34d399" : "#fb923c",
                             border: `1px solid ${txn.confidence==="HIGH"?"rgba(52,211,153,0.4)":"rgba(251,146,60,0.4)"}` }}>
                {txn.confidence}
              </span>
              <button onClick={() => unmatch(txn.id)}
                      style={{ background:"transparent", border:"1px solid #2a1a2e", color:"#6b5f7a",
                               borderRadius:6, padding:"3px 8px", cursor:"pointer", fontSize:10 }}>
                Undo
              </button>
            </div>
          ))}
          <div style={{ marginTop:12, padding:"10px 14px", background:"rgba(52,211,153,0.06)",
                        border:"1px solid rgba(52,211,153,0.2)", borderRadius:8, fontSize:12, color:"#6b5f7a" }}>
            In production: clicking Post creates journal entries in <code style={{ color:"#22d3ee" }}>llx_accounting_bookkeeping</code> via <code style={{ color:"#22d3ee" }}>JV-BK</code>
          </div>
        </div>
      )}
    </div>
  );
}

// ─── MAIN COMPONENT ───────────────────────────────────────────────────────────
export default function B3eqAdvancedUI() {
  const [tab, setTab] = useState("coa");

  const TABS = [
    { id:"coa",   label:"📊 COA Drilldown",       sub:"Interactive ledger tree" },
    { id:"recon", label:"🔗 Reconciliation Board", sub:"Bank matching workspace" },
  ];

  return (
    <div style={{ fontFamily:"'DM Sans','Segoe UI',sans-serif", background:"#08080d",
                  minHeight:"100vh", color:"#f0e8ff" }}>

      {/* Header */}
      <div style={{ background:"linear-gradient(135deg,#0a0610,#1a0a12,#0a0610)",
                    borderBottom:"2px solid #e4001b", padding:"18px 28px 14px" }}>
        <div style={{ display:"flex", alignItems:"center", gap:14, marginBottom:14 }}>
          <div style={{ width:42, height:42, borderRadius:10, display:"flex", alignItems:"center",
                        justifyContent:"center", fontFamily:"'Syne',sans-serif", fontSize:17,
                        fontWeight:900, color:"#fff",
                        background:"linear-gradient(135deg,#e4001b,#ff4d1a)",
                        boxShadow:"0 0 20px rgba(228,0,27,0.4)" }}>b3</div>
          <div>
            <div style={{ fontFamily:"'Syne',sans-serif", fontSize:18, fontWeight:900,
                          letterSpacing:"-.02em", color:"#f1f5f9" }}>
              b3Ɛq Advanced Accounting UI
            </div>
            <div style={{ fontSize:11, color:"#6b5f7a", letterSpacing:".08em", textTransform:"uppercase" }}>
              v2.0.0 · Drilldown COA Tree · Reconciliation Board
            </div>
          </div>
          <div style={{ marginLeft:"auto", display:"flex", gap:8 }}>
            {["NTA 2025","IFRS SMEs","v2.0.0"].map(t => (
              <span key={t} style={{ background:"rgba(228,0,27,0.12)", border:"1px solid rgba(228,0,27,0.35)",
                                     borderRadius:20, padding:"3px 10px", fontSize:10,
                                     color:"#e4001b", fontWeight:700, letterSpacing:".05em" }}>{t}</span>
            ))}
          </div>
        </div>
        {/* Tabs */}
        <div style={{ display:"flex", gap:6 }}>
          {TABS.map(t => (
            <button key={t.id} onClick={() => setTab(t.id)}
                    style={{ background: tab===t.id ? "linear-gradient(135deg,#e4001b,#ff4d1a)" : "transparent",
                             border: tab===t.id ? "none" : "1px solid #2a1a2e",
                             borderRadius:8, padding:"7px 16px", cursor:"pointer",
                             color: tab===t.id ? "#fff" : "#6b5f7a",
                             fontSize:12, fontWeight:700, whiteSpace:"nowrap" }}>
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {/* Content */}
      <div style={{ padding:"24px 28px", maxWidth:1100, margin:"0 auto" }}>

        {tab === "coa" && (
          <div>
            <div style={{ marginBottom:20 }}>
              <h2 style={{ fontFamily:"'Syne',sans-serif", fontSize:18, fontWeight:800,
                           color:"#f1f5f9", margin:"0 0 4px", letterSpacing:"-.02em" }}>
                Chart of Accounts — Drilldown Explorer
              </h2>
              <p style={{ margin:0, color:"#6b5f7a", fontSize:12.5 }}>
                Click any account to drill down into its ledger. Balance = current period movement.
              </p>
              <div style={{ height:2, background:"linear-gradient(90deg,#e4001b,#ff4d1a,transparent)", marginTop:12 }} />
            </div>

            {/* Balance sheet summary */}
            <div style={{ display:"grid", gridTemplateColumns:"repeat(4,1fr)", gap:12, marginBottom:20 }}>
              {[
                ["Total Assets",   COA_TREE.filter(a=>a.type==="ASSET").reduce((s,a)=>s+a.balance,0),     "#22d3ee"],
                ["Total Liabilities",Math.abs(COA_TREE.filter(a=>a.type==="LIABILITIES").reduce((s,a)=>s+a.balance,0)),"#f87171"],
                ["Total Revenue",  Math.abs(COA_TREE.filter(a=>a.type==="INCOME").reduce((s,a)=>s+a.balance,0)),  "#34d399"],
                ["Total Expenses", COA_TREE.filter(a=>a.type==="EXPENSE").reduce((s,a)=>s+a.balance,0),   "#fb923c"],
              ].map(([lbl,val,col]) => (
                <div key={lbl} style={{ background:"rgba(255,255,255,0.03)", border:`1px solid ${col}22`,
                                        borderRadius:10, padding:"12px 14px" }}>
                  <div style={{ fontSize:20, fontWeight:900, color:col, fontFamily:"'Syne',sans-serif" }}>{fmt(val)}</div>
                  <div style={{ fontSize:11, color:"#6b5f7a", textTransform:"uppercase", letterSpacing:".06em", marginTop:3 }}>{lbl}</div>
                </div>
              ))}
            </div>

            <COATree />
          </div>
        )}

        {tab === "recon" && (
          <div>
            <div style={{ marginBottom:20 }}>
              <h2 style={{ fontFamily:"'Syne',sans-serif", fontSize:18, fontWeight:800,
                           color:"#f1f5f9", margin:"0 0 4px", letterSpacing:"-.02em" }}>
                Bank Reconciliation Board
              </h2>
              <p style={{ margin:0, color:"#6b5f7a", fontSize:12.5 }}>
                Match bank feed transactions to ledger accounts. Auto-match uses narration keywords + suggested accounts from the Open Banking Sync workflow.
              </p>
              <div style={{ height:2, background:"linear-gradient(90deg,#e4001b,#ff4d1a,transparent)", marginTop:12 }} />
            </div>
            <ReconciliationBoard />
          </div>
        )}

      </div>

      {/* Footer */}
      <div style={{ borderTop:"1px solid #2a1a2e", padding:"14px 28px",
                    display:"flex", justifyContent:"space-between",
                    fontSize:11, color:"#334155" }}>
        <span><strong style={{ color:"#e4001b" }}>b3Ɛq</strong> Nigerian Accountancy v2.0.0 · tagged: b3Ɛq Nigerian Accountancy</span>
        <span>© 2026 Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)</span>
      </div>
    </div>
  );
}
