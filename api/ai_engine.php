<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>WealthAI | Intelligence Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        body { font-family: 'Geist', sans-serif; background: #000; color: #fff; -webkit-font-smoothing: antialiased; }
        .glass { background: rgba(15, 15, 15, 0.7); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .neo-gradient { background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%); }
        .ai-glow { box-shadow: 0 0 30px rgba(99, 102, 241, 0.4); animation: pulse 2s infinite ease-in-out; }
        @keyframes pulse { 0%, 100% { transform: scale(1) translateY(-12px); opacity: 0.8; } 50% { transform: scale(1.05) translateY(-15px); opacity: 1; } }
        .text-gradient { background: linear-gradient(to bottom, #fff 30%, #52525b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-transition { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect } = React;

        const App = () => {
            const [view, setView] = useState('dash'); // dash, ai, settings
            const [assets, setAssets] = useState([]);
            const [aiResult, setAiResult] = useState(null);
            const [loadingAi, setLoadingAi] = useState(false);
            const [isModalOpen, setModalOpen] = useState(false);

            const fetchData = async () => {
                const res = await fetch('api/assets.php');
                const data = await res.json();
                setAssets(Array.isArray(data) ? data : []);
            };

            const runAI = async () => {
                setView('ai');
                setLoadingAi(true);
                try {
                    const res = await fetch('api/ai_engine.php');
                    const data = await res.json();
                    setAiResult(data);
                } finally { setLoadingAi(false); }
            };

            useEffect(() => { fetchData(); }, []);

            return (
                <div className="min-h-screen pb-32">
                    {/* Header */}
                    <header className="px-8 py-8 flex justify-between items-center sticky top-0 z-50 bg-black/50 backdrop-blur-lg">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 neo-gradient rounded-lg rotate-12 flex items-center justify-center font-black italic">W</div>
                            <span className="font-black tracking-tighter uppercase text-lg">WealthAI</span>
                        </div>
                        <div className="text-[10px] font-bold text-zinc-500 tracking-widest uppercase px-3 py-1 glass rounded-full">
                            {view === 'ai' ? 'Neural Processing' : 'Stable Feed'}
                        </div>
                    </header>

                    <main className="px-6 max-w-5xl mx-auto mt-6">
                        {view === 'dash' && (
                            <div className="page-transition">
                                <section className="text-center mb-12">
                                    <p className="text-zinc-500 text-[10px] font-black uppercase tracking-[0.3em] mb-2">Net Worth</p>
                                    <h2 className="text-6xl font-black italic text-gradient uppercase">€{assets.reduce((acc, a) => acc + (parseFloat(a.quantity) * parseFloat(a.buy_price)), 0).toLocaleString()}</h2>
                                </section>

                                <div className="grid gap-6">
                                    <div className="glass p-8 rounded-[40px]">
                                        <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-6">Asset Allocation</h3>
                                        {assets.length === 0 ? <p className="text-zinc-600 italic">No assets found...</p> : 
                                            assets.map((a, i) => (
                                                <div key={i} className="flex items-center justify-between py-4 border-b border-white/5">
                                                    <div>
                                                        <p className="font-black uppercase">{a.symbol}</p>
                                                        <p className="text-[10px] text-zinc-500 font-bold">{a.asset_name}</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="font-bold">€{(parseFloat(a.quantity) * parseFloat(a.buy_price)).toLocaleString()}</p>
                                                        <p className="text-[10px] text-indigo-400 font-black">{a.quantity} UNITS</p>
                                                    </div>
                                                </div>
                                            ))
                                        }
                                    </div>
                                </div>
                            </div>
                        )}

                        {view === 'ai' && (
                            <div className="page-transition">
                                <h2 className="text-4xl font-black italic text-gradient uppercase text-center mb-10">Intelligence Engine</h2>
                                {loadingAi ? (
                                    <div className="flex flex-col items-center py-20">
                                        <div className="w-12 h-12 border-2 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin mb-4"></div>
                                        <p className="text-zinc-500 font-black text-xs uppercase tracking-widest animate-pulse">Scanning Global Markets...</p>
                                    </div>
                                ) : (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="glass p-6 rounded-[30px] text-center">
                                                <p className="text-[9px] font-black text-zinc-500 uppercase mb-1">Sentiment</p>
                                                <p className="text-xl font-black italic text-indigo-400">{aiResult?.sentiment}</p>
                                            </div>
                                            <div className="glass p-6 rounded-[30px] text-center">
                                                <p className="text-[9px] font-black text-zinc-500 uppercase mb-1">Health Score</p>
                                                <p className="text-3xl font-black italic">{aiResult?.score}%</p>
                                            </div>
                                        </div>
                                        <div className="glass p-8 rounded-[40px] border-indigo-500/20">
                                            <h3 className="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-4">AI Recommendations</h3>
                                            <ul className="space-y-4">
                                                {aiResult?.insights.map((ins, i) => (
                                                    <li key={i} className="text-sm font-medium text-zinc-300 leading-relaxed bg-white/5 p-4 rounded-2xl border border-white/5">
                                                        ⚡ {ins}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {view === 'settings' && (
                            <div className="page-transition">
                                <h2 className="text-4xl font-black italic text-gradient uppercase mb-10">Settings</h2>
                                <div className="glass rounded-[40px] overflow-hidden">
                                    <div className="p-6 border-b border-white/5 flex justify-between items-center hover:bg-white/5 transition-colors cursor-pointer">
                                        <span className="font-bold">Segurança (Encriptação Ativa)</span>
                                        <span className="text-emerald-500 text-xs font-black">ON</span>
                                    </div>
                                    <div className="p-6 border-b border-white/5 flex justify-between items-center hover:bg-white/5 transition-colors cursor-pointer">
                                        <span className="font-bold">Moeda Base</span>
                                        <span className="text-zinc-400 text-xs font-black">EUR (€)</span>
                                    </div>
                                    <div className="p-6 flex justify-between items-center hover:bg-rose-500/10 transition-colors cursor-pointer group">
                                        <span className="font-bold group-hover:text-rose-500">Limpar Todos os Dados</span>
                                        <span className="text-zinc-600 group-hover:text-rose-500 text-xs font-black italic">RESET</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </main>

                    {/* Navigation Bar */}
                    <nav className="fixed bottom-8 left-1/2 -translate-x-1/2 w-[90%] max-w-sm glass rounded-[35px] h-20 flex justify-around items-center px-6 z-50">
                        <button onClick={() => setView('dash')} className={`flex flex-col items-center gap-1 ${view === 'dash' ? 'text-indigo-400' : 'text-zinc-600'}`}>
                            <span className="text-[10px] font-black uppercase italic tracking-tighter">Dash</span>
                        </button>

                        <button onClick={runAI} className="w-16 h-16 neo-gradient rounded-full flex items-center justify-center ai-glow transition-transform active:scale-90">
                            <span className="text-white font-black italic text-xl">AI</span>
                        </button>

                        <button onClick={() => setView('settings')} className={`flex flex-col items-center gap-1 ${view === 'settings' ? 'text-indigo-400' : 'text-zinc-600'}`}>
                            <span className="text-[10px] font-black uppercase italic tracking-tighter">Defs</span>
                        </button>
                    </nav>
                </div>
            );
        };

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>