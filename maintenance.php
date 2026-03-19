<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Maintenance Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { primary: "#a0f000", "bg-dark": "#020302" }
                }
            }
        }
    </script>
    <style>
        body { background-color: #020302; font-family: 'Inter', sans-serif; overflow: hidden; }
        .glow { text-shadow: 0 0 20px rgba(160, 240, 0, 0.4); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="text-center space-y-8 animate-fade-in px-4">
        <div class="inline-flex size-24 rounded-[2rem] bg-primary/10 border border-primary/20 items-center justify-center text-primary shadow-[0_0_50px_-10px_#a0f000]">
            <span class="material-symbols-outlined text-[3.5rem] font-black animate-pulse">construction</span>
        </div>
        <div>
            <h1 class="text-4xl md:text-6xl font-black text-white italic tracking-tighter uppercase">System <span class="text-primary not-italic glow">Locked</span></h1>
            <p class="text-[10px] md:text-xs font-mono text-slate-500 uppercase tracking-[0.5em] mt-2">Protocol: Level 4 Maintenance Underway</p>
        </div>
        <p class="max-w-md mx-auto text-slate-400 text-sm font-bold leading-relaxed uppercase tracking-wider">
            Our operative nodes are currently performing deep-core diagnostics. Access to the matrix is temporarily restricted to administrative clearance only.
        </p>
        <div class="pt-8 flex flex-col items-center gap-4">
            <div class="flex items-center gap-3 px-6 py-3 bg-white/5 border border-white/10 rounded-2xl">
                <div class="size-2 rounded-full bg-primary animate-ping"></div>
                <span class="text-[10px] font-mono text-primary uppercase font-bold tracking-widest leading-none">Real-time Sync in Progress</span>
            </div>
            <a href="admin/dashboard.php" class="text-[10px] font-black text-slate-600 hover:text-primary uppercase tracking-widest transition-colors">Admin Clearing Node</a>
        </div>
    </div>
</body>
</html>
