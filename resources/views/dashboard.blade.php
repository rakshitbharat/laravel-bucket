<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraBucket Admin Panel</title>
    <!-- Tailwind CSS v3 Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                            950: '#2e1065',
                        }
                    },
                    boxShadow: {
                        'glow-purple': '0 0 20px rgba(139, 92, 246, 0.15)',
                        'glow-indigo': '0 0 20px rgba(99, 102, 241, 0.15)',
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #05050a;
        }
        .glass-panel {
            background: rgba(10, 10, 20, 0.55);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-card {
            background: rgba(17, 17, 30, 0.35);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(139, 92, 246, 0.2);
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.5), inset 0 0 0 1px rgba(139, 92, 246, 0.1);
            transform: translateY(-2px);
        }
        .glass-input {
            background: rgba(5, 5, 10, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
        }
        .glass-input:focus {
            border-color: rgba(139, 92, 246, 0.5);
            background: rgba(5, 5, 10, 0.85);
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.1);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.15);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.35);
        }
        @keyframes progress-decay {
            from { width: 100%; }
            to { width: 0%; }
        }
        .toast-progress {
            animation: progress-decay 5s linear forwards;
        }
    </style>
</head>
<body class="text-slate-200 min-h-screen flex flex-col antialiased bg-[#05050a] selection:bg-brand-600 selection:text-white relative overflow-x-hidden"
      x-data="larabucketAdmin()"
      x-init="initApp()">

    <!-- Ambient glowing backgrounds -->
    <div class="fixed pointer-events-none inset-0 overflow-hidden z-0">
        <div class="absolute -top-[40%] -left-[30%] w-[90%] h-[90%] rounded-full bg-violet-900/10 blur-[130px] animate-pulse" style="animation-duration: 10s;"></div>
        <div class="absolute -bottom-[40%] -right-[30%] w-[90%] h-[90%] rounded-full bg-indigo-900/10 blur-[130px] animate-pulse" style="animation-duration: 12s;"></div>
        <div class="absolute top-[25%] left-[50%] -translate-x-1/2 w-[70%] h-[70%] rounded-full bg-fuchsia-950/5 blur-[160px]"></div>
    </div>

    <!-- Toast Notifications -->
    <div class="fixed top-6 right-6 z-[100] flex flex-col gap-3.5 w-full max-w-sm pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div :class="{
                    'border-emerald-500/30 bg-emerald-950/80 text-emerald-100 shadow-emerald-950/20': toast.type === 'success',
                    'border-rose-500/30 bg-rose-950/80 text-rose-100 shadow-rose-950/20': toast.type === 'error',
                    'border-cyan-500/30 bg-cyan-950/80 text-cyan-100 shadow-cyan-950/20': toast.type === 'info'
                 }"
                 class="glass-panel p-4 rounded-2xl border flex items-start gap-3 shadow-[0_15px_30px_rgba(0,0,0,0.55)] transition-all duration-300 transform translate-y-0 pointer-events-auto relative overflow-hidden"
                 x-transition:enter="transition ease-out duration-350"
                 x-transition:enter-start="opacity-0 translate-y-[-10px] scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-250"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <div class="flex-shrink-0 mt-0.5">
                    <template x-if="toast.type === 'success'">
                        <i data-lucide="check-circle-2" class="h-4.5 w-4.5 text-emerald-400"></i>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <i data-lucide="alert-circle" class="h-4.5 w-4.5 text-rose-400"></i>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <i data-lucide="info" class="h-4.5 w-4.5 text-cyan-400"></i>
                    </template>
                </div>
                
                <div class="flex-1 text-xs font-semibold tracking-wide pr-2" x-text="toast.message"></div>
                <button @click="removeToast(toast.id)" class="text-slate-400 hover:text-white transition-colors duration-150 p-0.5 -mt-1 -mr-1">&times;</button>
                
                <!-- Decay Progress Bar -->
                <div :class="{
                        'bg-emerald-500': toast.type === 'success',
                        'bg-rose-500': toast.type === 'error',
                        'bg-cyan-500': toast.type === 'info'
                     }"
                     class="absolute bottom-0 left-0 h-[3px] opacity-40 toast-progress"></div>
            </div>
        </template>
    </div>

    <!-- Login Dialog Overlay -->
    <div x-show="!authenticated" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-2xl px-4"
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         style="display: none;">
        
        <!-- Glowing background behind login card -->
        <div class="absolute w-[500px] h-[500px] rounded-full bg-violet-600/10 blur-[120px] pointer-events-none animate-pulse"></div>

        <div class="glass-panel w-full max-w-md p-10 rounded-[2.25rem] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.8)] border border-white/[0.07] relative overflow-hidden flex flex-col items-center">
            <div class="absolute -top-16 -right-16 w-40 h-40 bg-violet-600/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 -left-16 w-40 h-40 bg-fuchsia-600/10 rounded-full blur-3xl"></div>
            
            <div class="mb-8 flex flex-col items-center">
                <div class="h-16 w-16 bg-gradient-to-tr from-violet-600 via-indigo-600 to-fuchsia-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 ring-2 ring-white/10 mb-4 animate-bounce" style="animation-duration: 3s;">
                    <i data-lucide="box" class="h-8 w-8 text-white"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight mb-2 bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">LaraBucket Console</h2>
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-widest text-center">Manage Centralized Storage</p>
            </div>

            <form @submit.prevent="login()" class="space-y-6 w-full">
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-4 top-3.5 h-4.5 w-4.5 text-slate-500"></i>
                        <input type="email" 
                               x-model="loginForm.email" 
                               class="w-full glass-input rounded-xl py-3.5 pl-12 pr-4 text-sm text-white focus:outline-none" 
                               placeholder="super@admin.com" 
                               required>
                    </div>
                </div>

                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-4 top-3.5 h-4.5 w-4.5 text-slate-500"></i>
                        <input :type="showPassword ? 'text' : 'password'" 
                               x-model="loginForm.password" 
                               class="w-full glass-input rounded-xl py-3.5 pl-12 pr-12 text-sm text-white focus:outline-none" 
                               placeholder="••••••••" 
                               required>
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-3.5 text-slate-500 hover:text-white transition-colors duration-150">
                            <i x-show="showPassword" data-lucide="eye-off" class="h-4.5 w-4.5" style="display: none;"></i>
                            <i x-show="!showPassword" data-lucide="eye" class="h-4.5 w-4.5"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-4 bg-gradient-to-r from-violet-600 via-indigo-600 to-fuchsia-600 hover:from-violet-500 hover:via-indigo-500 hover:to-fuchsia-500 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition duration-300 shadow-xl shadow-violet-500/20 flex justify-center items-center gap-2 hover:scale-[1.01] active:scale-[0.99] mt-2">
                    <span x-show="!loggingIn">Enter Console</span>
                    <span x-show="loggingIn" class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Authenticated Console Layout -->
    <div x-show="authenticated" class="flex-1 flex flex-col lg:flex-row overflow-hidden relative z-10 w-full" style="display: none;">
        
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-72 bg-slate-950/40 backdrop-blur-2xl border-b lg:border-b-0 lg:border-r border-white/[0.05] flex flex-col flex-shrink-0">
            
            <!-- Branding Header -->
            <div class="p-6 border-b border-white/[0.05] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-gradient-to-tr from-violet-600 via-indigo-600 to-fuchsia-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/25 ring-2 ring-white/5">
                        <i data-lucide="box" class="h-5 w-5 text-white"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-base font-extrabold text-white tracking-tight leading-none">LaraBucket</span>
                        <span class="text-[9px] text-slate-400 mt-1.5 font-medium tracking-wide">Unified Storage Console</span>
                    </div>
                </div>
                <span class="text-[9px] bg-violet-500/10 text-violet-300 border border-violet-500/30 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider shadow-[0_0_8px_rgba(139,92,246,0.1)]">Self-Hosted</span>
            </div>

            <!-- Global Storage Capacity Progress -->
            <div class="p-6 border-b border-white/[0.03]">
                <p class="text-[9px] text-slate-400 uppercase tracking-widest font-bold mb-3 flex items-center gap-1.5">
                    <i data-lucide="hard-drive" class="h-3 w-3 text-violet-400"></i> Storage Stats
                </p>
                <div class="bg-slate-950/50 p-4 rounded-xl border border-white/[0.03]">
                    <div class="flex justify-between text-xs font-semibold text-slate-300 mb-2">
                        <span>Used</span>
                        <span class="text-white font-bold" x-text="formatSize(totalStorageUsed)">0 MB</span>
                    </div>
                    <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-white/[0.04] relative">
                        <div class="bg-gradient-to-r from-violet-500 via-indigo-500 to-fuchsia-500 h-full rounded-full transition-all duration-500 shadow-[0_0_8px_rgba(139,92,246,0.3)]" 
                             :style="`width: ${Math.min(100, (totalStorageUsed / (totalStorageLimit * 1024 * 1024)) * 100)}%`"></div>
                    </div>
                    <div class="flex justify-between text-[9px] text-slate-400 mt-2 font-medium">
                        <span>Max Quota</span>
                        <span class="text-slate-300 font-bold" x-text="`${totalStorageLimit} MB`">0 MB</span>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 p-6 space-y-1.5 overflow-y-auto">
                <p class="text-[9px] text-slate-500 uppercase tracking-widest font-bold px-2.5 mb-2.5">Menu</p>
                
                <button @click="viewMode = 'dashboard'" 
                        :class="viewMode === 'dashboard' ? 'bg-gradient-to-r from-violet-600/10 to-indigo-600/10 text-violet-400 border-violet-500/20 shadow-[0_0_15px_rgba(139,92,246,0.05)] font-bold' : 'text-slate-400 hover:text-slate-100 hover:bg-white/[0.02] border-transparent'"
                        class="w-full flex items-center gap-3 px-4.5 py-3 rounded-xl transition-all duration-150 text-xs border text-left font-bold">
                    <i data-lucide="layout-dashboard" class="h-4.5 w-4.5"></i>
                    Dashboard Panel
                </button>

                <div class="pt-5">
                    <p class="text-[9px] text-slate-500 uppercase tracking-widest font-bold px-2.5 mb-2.5 font-bold">Namespaces</p>
                    <div class="space-y-1 max-h-60 overflow-y-auto pr-1">
                        <template x-for="b in buckets" :key="b.id">
                            <button @click="browseBucket(b)"
                                    :class="(viewMode === 'browser' && currentBucket?.id === b.id) ? 'bg-gradient-to-r from-violet-600/10 to-indigo-600/10 text-violet-400 border-violet-500/20 font-bold' : 'text-slate-400 hover:text-slate-100 hover:bg-white/[0.01] border-transparent'"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs transition border text-left font-semibold">
                                <span class="truncate pr-2 flex items-center gap-2">
                                    <i data-lucide="folder" class="h-4 w-4 text-violet-400/70"></i>
                                    <span x-text="b.name"></span>
                                </span>
                                <span class="text-[8px] bg-slate-900 border border-white/[0.04] px-1.5 py-0.5 rounded text-slate-400 font-bold" x-text="`${Math.round(b.storageUsedMb)}MB`"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </nav>

            <!-- Active User Profile Section -->
            <div class="p-6 border-t border-white/[0.05] bg-slate-950/20 flex items-center justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="h-9 w-9 bg-gradient-to-tr from-violet-600 to-indigo-600 rounded-full flex items-center justify-center font-extrabold text-white text-sm uppercase shadow-md shadow-violet-500/10 border border-violet-400/20" x-text="adminEmail[0]">U</div>
                    <div class="overflow-hidden">
                        <div class="text-xs font-bold text-white truncate">Administrator</div>
                        <div class="text-[9px] text-slate-400 truncate" x-text="adminEmail">admin@larabucket.com</div>
                    </div>
                </div>
                <button @click="logout()" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition duration-150" title="Sign Out">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                </button>
            </div>
        </aside>

        <!-- Main Console Body -->
        <main class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Dashboard View -->
            <div x-show="viewMode === 'dashboard'" class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-8" x-transition>
                
                <!-- Main Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">System Dashboard</h1>
                        <p class="text-xs text-slate-400 mt-1">Configure storage namespaces, manage access tokens, and view utilization metrics.</p>
                    </div>
                    <button @click="openCreateModal()" 
                            class="px-5 py-2.5 bg-gradient-to-r from-violet-600 via-indigo-600 to-fuchsia-600 hover:from-violet-500 hover:via-indigo-500 hover:to-fuchsia-500 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition duration-300 shadow-lg shadow-violet-500/25 flex items-center gap-2 hover:scale-[1.01]">
                        <i data-lucide="plus-circle" class="h-4 w-4"></i>
                        Create Bucket
                    </button>
                </div>

                <!-- Stats Cards Section -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="glass-panel p-5 rounded-2xl border border-white/[0.04] flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="h-12 w-12 rounded-xl bg-violet-500/10 flex items-center justify-center border border-violet-500/20 shadow-inner">
                            <i data-lucide="layers" class="h-5 w-5 text-violet-400"></i>
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Active Buckets</div>
                            <div class="text-2xl font-extrabold text-white mt-1" x-text="buckets.length">0</div>
                        </div>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-white/[0.04] flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="h-12 w-12 rounded-xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20 shadow-inner">
                            <i data-lucide="hard-drive" class="h-5 w-5 text-indigo-400"></i>
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total Used Space</div>
                            <div class="text-2xl font-extrabold text-white mt-1" x-text="formatSize(totalStorageUsed)">0 Bytes</div>
                        </div>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-white/[0.04] flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20 shadow-inner">
                            <span class="relative flex h-2.5 w-2.5 mr-0.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Console Engine</div>
                            <div class="text-lg font-bold text-emerald-400 mt-1">Online</div>
                        </div>
                    </div>
                </div>

                <!-- Usage Chart Panel -->
                <div class="glass-panel p-6 rounded-3xl border border-white/[0.04] shadow-xl relative overflow-hidden">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-6 flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="h-4 w-4 text-violet-400"></i> Storage Allocation Chart
                    </h3>
                    <div class="h-56 flex items-end justify-between gap-6 px-4 pt-4 border-b border-white/[0.06] overflow-x-auto relative min-h-[14rem]">
                        
                        <!-- Grid Lines -->
                        <div class="absolute inset-x-0 top-0 bottom-0 flex flex-col justify-between pointer-events-none pb-4">
                            <div class="border-t border-white/[0.03] w-full"></div>
                            <div class="border-t border-white/[0.03] w-full"></div>
                            <div class="border-t border-white/[0.03] w-full"></div>
                            <div class="border-t border-white/[0.03] w-full"></div>
                        </div>

                        <template x-for="b in buckets" :key="b.id">
                            <div class="flex-1 flex flex-col items-center gap-3 group min-w-[70px] max-w-[130px] z-10">
                                <div class="w-full bg-slate-900/40 hover:bg-slate-900/70 border border-white/[0.03] rounded-t-xl transition-all duration-300 relative flex flex-col justify-end h-40">
                                    
                                    <!-- Glow Fill Bar -->
                                    <div class="bg-gradient-to-t from-indigo-500 via-violet-500 to-fuchsia-500 rounded-t-lg transition-all duration-500 relative"
                                         :style="`height: ${Math.max(4, Math.min(100, (b.storageUsedMb / b.storageLimitMb) * 100))}%`">
                                        <div class="absolute inset-0 bg-white/10 rounded-t-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                                    </div>

                                    <!-- Interactive Tooltip -->
                                    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2 glass-panel px-3 py-1.5 rounded-xl text-[10px] opacity-0 group-hover:opacity-100 transition-all duration-200 z-20 whitespace-nowrap shadow-2xl border border-violet-500/20 text-white font-semibold flex flex-col items-center">
                                        <span x-text="`${Math.round(b.storageUsedMb)} MB`" class="text-violet-300 font-bold"></span>
                                        <span x-text="`Limit: ${b.storageLimitMb} MB`" class="text-slate-400 text-[8px]"></span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 truncate w-full text-center" x-text="b.name"></span>
                            </div>
                        </template>
                        
                        <div x-show="buckets.length === 0" class="w-full h-full flex flex-col items-center justify-center text-slate-500 text-xs py-14 z-10">
                            <i data-lucide="database-backup" class="h-10 w-10 text-slate-700 mb-2"></i>
                            Create a storage namespace to populate usage stats
                        </div>
                    </div>
                </div>

                <!-- Buckets List Panel -->
                <div class="glass-panel rounded-3xl overflow-hidden border border-white/[0.04] shadow-2xl">
                    <div class="px-6 py-5 border-b border-white/[0.04] flex justify-between items-center bg-slate-900/10">
                        <h2 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="folders" class="h-4 w-4 text-violet-400"></i> Allocated Namespaces
                        </h2>
                        <span class="text-[10px] bg-slate-950 border border-white/[0.06] px-2.5 py-1 rounded-full text-slate-400 font-bold" x-text="`TOTAL: ${buckets.length}`"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-950/30 text-slate-400 border-b border-white/[0.04]">
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px]">Bucket Name</th>
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px]">Slug</th>
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px]">Quota Limit</th>
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px]">Space Used</th>
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px]">Reference Owner</th>
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px]">API Token</th>
                                    <th class="px-6 py-4.5 font-bold uppercase tracking-wider text-[9px] text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.03]">
                                <template x-for="b in buckets" :key="b.id">
                                    <tr class="hover:bg-white/[0.01] transition-all duration-150">
                                        <td class="px-6 py-4 font-bold text-white text-xs" x-text="b.name"></td>
                                        <td class="px-6 py-4">
                                            <span class="bg-slate-950 border border-white/[0.04] px-2.5 py-1 rounded-lg text-violet-400 font-bold text-[10px] font-mono shadow-[0_0_8px_rgba(139,92,246,0.05)]" x-text="b.slug"></span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-200 font-semibold" x-text="`${b.storageLimitMb} MB`"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="text-slate-200 font-bold text-[10px] whitespace-nowrap" x-text="`${Math.round(b.storageUsedMb)} MB`"></span>
                                                <div class="w-16 bg-slate-950 border border-white/[0.04] rounded-full h-1.5 overflow-hidden relative">
                                                    <div class="bg-gradient-to-r from-violet-500 to-indigo-500 h-full rounded-full" 
                                                         :style="`width: ${Math.min(100, (b.storageUsedMb / b.storageLimitMb) * 100)}%`"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-300 font-medium" x-text="b.ownerEmail || '—'"></td>
                                        <td class="px-6 py-4 font-mono">
                                            <div class="flex items-center gap-2 bg-slate-950 border border-white/[0.04] px-2.5 py-1 rounded-xl w-max">
                                                <span class="text-slate-300 text-[10px] font-semibold" 
                                                      x-text="showKeyMap[b.id] ? b.secretKey : '••••••••••••••••••••••••••••••••'"></span>
                                                <button @click="toggleKey(b.id)" class="text-slate-500 hover:text-white transition ml-1 p-0.5 rounded hover:bg-white/5">
                                                    <i x-show="showKeyMap[b.id]" data-lucide="eye-off" class="h-3.5 w-3.5" style="display: none;"></i>
                                                    <i x-show="!showKeyMap[b.id]" data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                </button>
                                                <button @click="copyToClipboard(b.secretKey, 'API Token copied!')" class="text-slate-500 hover:text-violet-400 transition p-0.5 rounded hover:bg-white/5">
                                                    <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button @click="browseBucket(b)" class="px-3 py-1.5 bg-violet-600/10 hover:bg-violet-600 border border-violet-500/20 hover:border-violet-500 text-violet-400 hover:text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                                    <i data-lucide="folder-open" class="h-3.5 w-3.5"></i> Browse
                                                </button>
                                                <button @click="openEditModal(b)" class="p-2 bg-slate-900/60 border border-white/[0.04] text-slate-400 hover:text-white rounded-xl transition hover:border-slate-700 hover:bg-slate-900" title="Edit Namespace">
                                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                                </button>
                                                <button @click="deleteBucket(b)" class="p-2 bg-slate-900/60 border border-white/[0.04] text-rose-400/80 hover:text-white hover:bg-rose-600 hover:border-rose-500 rounded-xl transition" title="Delete Namespace">
                                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="buckets.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-semibold bg-slate-950/10">
                                        <i data-lucide="package-open" class="h-10 w-10 text-slate-700 mx-auto mb-2"></i>
                                        No active storage namespaces configured. Click "Create Bucket" to configure.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
 
            <!-- File Browser View -->
            <div x-show="viewMode === 'browser'" class="flex-1 flex flex-col overflow-hidden" x-transition>
                
                <!-- Browser Toolbar Header -->
                <header class="p-6 border-b border-white/[0.05] bg-slate-950/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <button @click="viewMode = 'dashboard'" class="p-2 bg-slate-900/80 hover:bg-slate-800 rounded-xl border border-white/[0.04] text-slate-400 hover:text-white transition">
                                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                            </button>
                            <h1 class="text-xl lg:text-2xl font-extrabold text-white tracking-tight" x-text="currentBucket?.name">Bucket</h1>
                        </div>
                        
                        <!-- Breadcrumbs -->
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-3 font-semibold font-mono bg-slate-950 border border-white/[0.04] px-3.5 py-1.5 rounded-xl w-max">
                            <i data-lucide="folder" class="h-3.5 w-3.5 text-violet-400"></i>
                            <button @click="navigatePath('/')" class="hover:text-white transition">root</button>
                            <template x-for="(segment, idx) in currentPathSegments" :key="idx">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="chevron-right" class="h-3 w-3 text-slate-600"></i>
                                    <button @click="navigatePathIdx(idx)" class="hover:text-white transition" x-text="segment"></button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Upload Action buttons -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click="openFolderModal()" class="px-4 py-2.5 bg-slate-900 border border-white/[0.05] text-slate-200 hover:text-white hover:border-slate-700 font-bold rounded-xl text-xs transition">
                            <i data-lucide="folder-plus" class="inline-block h-4 w-4 mr-1"></i> New Folder
                        </button>
                        
                        <label class="px-4 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs transition shadow-lg shadow-violet-500/25 flex items-center gap-1.5 cursor-pointer hover:scale-[1.01]">
                            <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                            Upload File
                            <input type="file" @change="uploadFile($event)" class="hidden">
                        </label>
                    </div>
                </header>

                <!-- File Browser Workspace Area -->
                <div class="flex-1 flex flex-col md:flex-row overflow-hidden relative">
                    
                    <!-- File Browser Explorer Grid -->
                    <div class="flex-1 overflow-y-auto p-6 flex flex-col bg-slate-950/10"
                         @dragover.prevent="dragover = true"
                         @dragleave.prevent="dragover = false"
                         @drop.prevent="handleDrop($event)"
                         :class="dragover ? 'border-2 border-dashed border-violet-500 bg-violet-500/5' : ''">
                        
                        <!-- Drag drop notice banner -->
                        <div x-show="dragover" class="w-full bg-violet-600/15 border border-violet-500/30 p-4 rounded-xl text-center text-xs text-violet-300 mb-6 font-bold uppercase tracking-wider animate-pulse" style="display:none;">
                            Drop files here to upload instantly
                        </div>

                        <!-- Grid view -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-5">
                            
                            <!-- Directory Back Folder -->
                            <div x-show="currentPath !== '/'"
                                 @click="navigateUp()"
                                 class="glass-card border-white/[0.04] p-4 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-white/10 hover:bg-white/[0.02] transition text-center h-32 group select-none shadow-sm">
                                <i data-lucide="folder-up" class="h-10 w-10 text-slate-500 group-hover:text-violet-400 transition-colors duration-200"></i>
                                <span class="text-[10px] text-slate-400 mt-2.5 font-bold uppercase tracking-widest">Parent</span>
                            </div>

                            <!-- List of folders and files -->
                            <template x-for="item in files" :key="item.id">
                                <div @click="selectFile(item)"
                                     @dblclick="item.type === 'folder' ? navigatePath('/' + ltrim(base64Decode(item.id), '/').split('/').slice(1).join('/')) : null"
                                     :class="selectedFile?.id === item.id ? 'border-violet-500 bg-violet-500/10 ring-1 ring-violet-500/30 shadow-lg' : 'border-white/[0.04] hover:border-white/10 hover:bg-white/[0.01]'"
                                     class="glass-card p-4 rounded-2xl flex flex-col items-center justify-center cursor-pointer transition select-none relative group overflow-hidden shadow-sm h-32">
                                    
                                    <!-- Selection Indicator tag -->
                                    <div x-show="selectedFile?.id === item.id" class="absolute top-2.5 right-2.5 h-4 w-4 bg-violet-500 rounded-full flex items-center justify-center border border-violet-400 shadow-md">
                                        <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>

                                    <!-- Folder Icon -->
                                    <template x-if="item.type === 'folder'">
                                        <div class="h-12 w-12 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                            <i data-lucide="folder" class="h-11 w-11 text-amber-500/90 fill-amber-500/15"></i>
                                        </div>
                                    </template>

                                    <!-- File Icon / Image preview -->
                                    <template x-if="item.type === 'file'">
                                        <div class="h-12 w-12 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                            <template x-if="isImage(item.mimeType)">
                                                <div class="h-11 w-11 rounded-lg bg-cover bg-center border border-white/10 shadow-inner" 
                                                     :style="`background-image: url('${getFileUrl(item)}')`"></div>
                                            </template>
                                            <template x-if="!isImage(item.mimeType)">
                                                <i data-lucide="file-text" class="h-9 w-9 text-violet-400/90"></i>
                                            </template>
                                        </div>
                                    </template>

                                    <span class="text-xs font-bold text-slate-200 mt-3 truncate w-full px-1.5 text-center" 
                                          x-text="item.name" 
                                          :title="item.name"></span>
                                </div>
                            </template>

                        </div>

                        <!-- Empty folder state -->
                        <div x-show="files.length === 0 && currentPath === '/'" class="w-full flex-1 flex flex-col items-center justify-center py-24 text-slate-500 bg-slate-950/10 rounded-3xl border border-white/[0.02]">
                            <div class="h-16 w-16 bg-slate-900/60 border border-white/[0.04] rounded-2xl flex items-center justify-center mb-4">
                                <i data-lucide="inbox" class="h-8 w-8 text-slate-600"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-300">Storage Namespace is Empty</p>
                            <p class="text-xs text-slate-500 mt-1">Drag and drop files here or click "Upload File" to start storing assets.</p>
                        </div>
                    </div>

                    <!-- File Details Side Panel -->
                    <aside x-show="selectedFile" 
                           class="w-full md:w-80 bg-slate-950/95 backdrop-blur-2xl border-t md:border-t-0 md:border-l border-white/[0.05] flex flex-col overflow-y-auto p-6 absolute right-0 top-0 bottom-0 z-30"
                           style="display:none;"
                           x-transition:enter="transition ease-out duration-250"
                           x-transition:enter-start="opacity-0 translate-x-10"
                           x-transition:enter-end="opacity-100 translate-x-0"
                           x-transition:leave="transition ease-in duration-200"
                           x-transition:leave-start="opacity-100 translate-x-0"
                           x-transition:leave-end="opacity-0 translate-x-10">
                        
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/[0.04]">
                            <h3 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-1.5">
                                <i data-lucide="info" class="h-4 w-4 text-violet-400"></i> Properties
                            </h3>
                            <button @click="selectedFile = null" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <!-- Panel File Image Preview -->
                        <div class="glass-panel h-44 w-full rounded-2xl flex items-center justify-center overflow-hidden mb-6 border border-white/[0.04] bg-slate-900/20 relative shadow-inner">
                            <template x-if="selectedFile && selectedFile.type === 'folder'">
                                <i data-lucide="folder" class="h-16 w-16 text-amber-500/80 fill-amber-500/5"></i>
                            </template>
                            <template x-if="selectedFile && selectedFile.type === 'file'">
                                <div class="h-full w-full flex items-center justify-center relative">
                                    <template x-if="isImage(selectedFile.mimeType)">
                                        <img :src="getFileUrl(selectedFile)" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!isImage(selectedFile.mimeType)">
                                        <i data-lucide="file-text" class="h-14 w-14 text-violet-400"></i>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Metadata Attributes list -->
                        <div class="space-y-4 mb-8 text-xs bg-slate-950 border border-white/[0.03] p-4.5 rounded-2xl shadow-inner">
                            <div>
                                <span class="text-slate-500 font-bold uppercase tracking-wider text-[8px] block mb-1">Item Name</span>
                                <span class="text-white font-bold select-all break-all" x-text="selectedFile?.name"></span>
                            </div>
                            <template x-if="selectedFile?.type === 'file'">
                                <div>
                                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[8px] block mb-1">Content Type</span>
                                    <span class="text-slate-300 font-mono text-[9px] font-semibold select-all" x-text="selectedFile?.mimeType"></span>
                                </div>
                            </template>
                            <template x-if="selectedFile?.type === 'file'">
                                <div>
                                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[8px] block mb-1">Disk Size</span>
                                    <span class="text-slate-200 font-semibold" x-text="formatSize(selectedFile?.size || 0)"></span>
                                </div>
                            </template>
                            <div>
                                <span class="text-slate-500 font-bold uppercase tracking-wider text-[8px] block mb-1">Last Modified</span>
                                <span class="text-slate-300 font-semibold" x-text="selectedFile?.updatedAt"></span>
                            </div>
                        </div>

                        <!-- Sidebar Action buttons -->
                        <div class="space-y-3 mt-auto">
                            <template x-if="selectedFile?.type === 'file'">
                                <button @click="copyToClipboard(getFileUrl(selectedFile), 'Public URL copied!')" 
                                        class="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition flex justify-center items-center gap-1.5 shadow-lg shadow-violet-500/10">
                                    <i data-lucide="link" class="h-3.5 w-3.5"></i> Copy Public URL
                                </button>
                            </template>
                            <template x-if="selectedFile?.type === 'folder'">
                                <button @click="browseFolderFromSelection()"
                                        class="w-full py-3 bg-slate-900 border border-white/[0.05] text-slate-200 hover:text-white rounded-xl text-xs font-bold uppercase tracking-wider transition flex justify-center items-center gap-1.5">
                                    <i data-lucide="folder-open" class="h-3.5 w-3.5"></i> Enter Directory
                                </button>
                            </template>
                            <button @click="deleteFile(selectedFile)" 
                                    class="w-full py-3 bg-rose-600/10 hover:bg-rose-650 border border-rose-500/20 hover:border-rose-500 text-rose-400 hover:text-white font-bold rounded-xl text-xs uppercase tracking-wider transition flex justify-center items-center gap-1.5">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Delete Item
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals Layout Overlay -->
    <!-- Create Namespace Modal -->
    <div x-show="modals.create" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-md px-4" style="display:none;" x-transition>
        <div class="glass-panel w-full max-w-md p-6 rounded-3xl shadow-3xl border border-white/[0.05] relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 bg-violet-600/5 rounded-full blur-2xl"></div>
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-extrabold text-white tracking-tight flex items-center gap-2">
                    <i data-lucide="folder-plus" class="h-5 w-5 text-violet-400"></i> Create Storage Namespace
                </h3>
                <button @click="modals.create = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition">&times;</button>
            </div>
            <form @submit.prevent="createBucket()" class="space-y-4">
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Namespace Name</label>
                    <input type="text" x-model="bucketForm.name" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" placeholder="corporate-media" required>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Owner Email (Reference)</label>
                    <input type="email" x-model="bucketForm.ownerEmail" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" placeholder="admin@corp.com" required>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Storage Limit (MB)</label>
                    <input type="number" min="1" x-model="bucketForm.storageLimitMb" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" placeholder="1000" required>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modals.create = false" class="px-4.5 py-2.5 bg-slate-900 border border-white/[0.04] hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold uppercase tracking-wider transition">Cancel</button>
                    <button type="submit" class="px-4.5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition font-bold">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Namespace Modal -->
    <div x-show="modals.edit" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-md px-4" style="display:none;" x-transition>
        <div class="glass-panel w-full max-w-md p-6 rounded-3xl shadow-3xl border border-white/[0.05] relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 bg-violet-600/5 rounded-full blur-2xl"></div>

            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-extrabold text-white tracking-tight flex items-center gap-2">
                    <i data-lucide="pencil" class="h-5 w-5 text-violet-400"></i> Edit Storage Namespace
                </h3>
                <button @click="modals.edit = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition">&times;</button>
            </div>
            <form @submit.prevent="updateBucket()" class="space-y-4">
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Namespace Name</label>
                    <input type="text" x-model="editForm.name" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" required>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Owner Email (Reference)</label>
                    <input type="email" x-model="editForm.ownerEmail" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" required>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Storage Limit (MB)</label>
                    <input type="number" min="1" x-model="editForm.storageLimitMb" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" required>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modals.edit = false" class="px-4.5 py-2.5 bg-slate-900 border border-white/[0.04] hover:bg-slate-855 text-slate-300 rounded-xl text-xs font-bold uppercase tracking-wider transition">Cancel</button>
                    <button type="submit" class="px-4.5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Folder Modal -->
    <div x-show="modals.folder" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-md px-4" style="display:none;" x-transition>
        <div class="glass-panel w-full max-w-sm p-6 rounded-3xl shadow-3xl border border-white/[0.05] relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 bg-violet-600/5 rounded-full blur-2xl"></div>

            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-extrabold text-white tracking-tight flex items-center gap-2">
                    <i data-lucide="folder-plus" class="h-5 w-5 text-violet-400"></i> Create Folder
                </h3>
                <button @click="modals.folder = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition">&times;</button>
            </div>
            <form @submit.prevent="createFolder()">
                <div class="mb-6">
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Folder Name</label>
                    <input type="text" x-model="folderForm.name" class="w-full glass-input rounded-xl py-2.5 px-4 text-sm text-white focus:outline-none" placeholder="assets" required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="modals.folder = false" class="px-4.5 py-2.5 bg-slate-900 border border-white/[0.04] hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold uppercase tracking-wider transition">Cancel</button>
                    <button type="submit" class="px-4.5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Logic -->
    <script>
        function larabucketAdmin() {
            return {
                authenticated: false,
                loggingIn: false,
                adminEmail: 'admin@larabucket.com',
                adminToken: '',
                viewMode: 'dashboard',
                
                buckets: [],
                totalStorageUsed: 0,
                totalStorageLimit: 0,
                
                showKeyMap: {},
                toasts: [],
                dragover: false,
                showPassword: false,
                
                loginForm: { email: '', password: '' },
                bucketForm: { name: '', ownerEmail: '', storageLimitMb: 1000 },
                editForm: { id: '', name: '', ownerEmail: '', storageLimitMb: 1000 },
                folderForm: { name: '' },
                
                currentBucket: null,
                currentPath: '/',
                currentPathSegments: [],
                files: [],
                selectedFile: null,
                
                modals: { create: false, edit: false, folder: false },

                initApp() {
                    const token = localStorage.getItem('larabucket_admin_token');
                    const email = localStorage.getItem('larabucket_admin_email');
                    if (token && email) {
                        this.authenticated = true;
                        this.adminToken = token;
                        this.adminEmail = email;
                        this.loadBuckets();
                    }
                    this.refreshIcons();
                },

                login() {
                    this.loggingIn = true;
                    fetch('/api/auth/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.loginForm)
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Invalid credentials');
                        return res.json();
                    })
                    .then(data => {
                        this.authenticated = true;
                        this.adminToken = data.token;
                        this.adminEmail = data.user.email;
                        localStorage.setItem('larabucket_admin_token', data.token);
                        localStorage.setItem('larabucket_admin_email', data.user.email);
                        this.showToast('success', 'Logged in successfully!');
                        this.loadBuckets();
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    })
                    .finally(() => {
                        this.loggingIn = false;
                    });
                },

                logout() {
                    this.authenticated = false;
                    this.adminToken = '';
                    localStorage.removeItem('larabucket_admin_token');
                    localStorage.removeItem('larabucket_admin_email');
                    this.showToast('info', 'Logged out.');
                },

                loadBuckets() {
                    fetch('/api/buckets', {
                        headers: { 'Authorization': 'Bearer ' + this.adminToken }
                    })
                    .then(res => {
                        if (res.status === 401) {
                            this.logout();
                            throw new Error('Session expired');
                        }
                        return res.json();
                    })
                    .then(data => {
                        this.buckets = data;
                        this.calculateStats();
                        this.refreshIcons();
                    })
                    .catch(err => {
                        this.showToast('error', 'Error loading buckets: ' + err.message);
                    });
                },

                calculateStats() {
                    this.totalStorageLimit = this.buckets.reduce((acc, b) => acc + b.storageLimitMb, 0);
                    this.totalStorageUsed = this.buckets.reduce((acc, b) => acc + (b.storageUsedMb * 1024 * 1024), 0);
                },

                toggleKey(id) {
                    this.showKeyMap[id] = !this.showKeyMap[id];
                    this.refreshIcons();
                },

                openCreateModal() {
                    this.bucketForm = { name: '', ownerEmail: '', storageLimitMb: 1000 };
                    this.modals.create = true;
                    this.refreshIcons();
                },

                createBucket() {
                    fetch('/api/buckets', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + this.adminToken
                        },
                        body: JSON.stringify(this.bucketForm)
                    })
                    .then(res => {
                        if (!res.ok) return res.json().then(d => { throw new Error(d.message || 'Failed') });
                        return res.json();
                    })
                    .then(data => {
                        this.modals.create = false;
                        this.showToast('success', 'Namespace created successfully!');
                        this.loadBuckets();
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    });
                },

                openEditModal(bucket) {
                    this.editForm = {
                        id: bucket.id,
                        name: bucket.name,
                        ownerEmail: bucket.ownerEmail,
                        storageLimitMb: bucket.storageLimitMb
                    };
                    this.modals.edit = true;
                    this.refreshIcons();
                },

                updateBucket() {
                    fetch('/api/buckets/' + this.editForm.id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + this.adminToken
                        },
                        body: JSON.stringify(this.editForm)
                    })
                    .then(res => {
                        if (!res.ok) return res.json().then(d => { throw new Error(d.message || 'Failed') });
                        return res.json();
                    })
                    .then(data => {
                        this.modals.edit = false;
                        this.showToast('success', 'Namespace updated successfully!');
                        this.loadBuckets();
                        
                        if (this.currentBucket && this.currentBucket.id === this.editForm.id) {
                            this.currentBucket = data;
                        }
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    });
                },

                deleteBucket(bucket) {
                    if (!confirm('Are you absolutely sure you want to delete this storage namespace and ALL its contents permanently? This cannot be undone.')) return;
                    fetch('/api/buckets/' + bucket.id, {
                        method: 'DELETE',
                        headers: { 'Authorization': 'Bearer ' + this.adminToken }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Delete failed');
                        this.showToast('success', 'Namespace deleted.');
                        this.loadBuckets();
                        if (this.currentBucket?.id === bucket.id) {
                            this.viewMode = 'dashboard';
                            this.currentBucket = null;
                        }
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    });
                },

                browseBucket(bucket) {
                    this.currentBucket = bucket;
                    this.viewMode = 'browser';
                    this.navigatePath('/');
                },

                loadFiles() {
                    fetch(`/api/buckets/${this.currentBucket.id}/files?path=${encodeURIComponent(this.currentPath)}`, {
                        headers: { 'Authorization': 'Bearer ' + this.adminToken }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.files = data;
                        this.selectedFile = null;
                        this.refreshIcons();
                    })
                    .catch(err => {
                        this.showToast('error', 'Failed to load files');
                    });
                },

                navigatePath(path) {
                    this.currentPath = path;
                    const cleanPath = path.replace(/^\/|\/$/g, '');
                    this.currentPathSegments = cleanPath ? cleanPath.split('/') : [];
                    this.loadFiles();
                },

                navigatePathIdx(idx) {
                    const newPath = '/' + this.currentPathSegments.slice(0, idx + 1).join('/');
                    this.navigatePath(newPath);
                },

                navigateUp() {
                    if (this.currentPath === '/') return;
                    const segments = this.currentPath.replace(/^\/|\/$/g, '').split('/');
                    segments.pop();
                    const parentPath = '/' + segments.join('/');
                    this.navigatePath(parentPath);
                },

                selectFile(item) {
                    this.selectedFile = item;
                    this.refreshIcons();
                },

                browseFolderFromSelection() {
                    const nextPath = '/' + ltrim(base64Decode(this.selectedFile.id), '/').split('/').slice(1).join('/');
                    this.navigatePath(nextPath);
                },

                getFileUrl(item) {
                    const baseServerUrl = window.location.origin;
                    return baseServerUrl + '/storage/' + base64Decode(item.id);
                },

                isImage(mime) {
                    return mime && mime.startsWith('image/');
                },

                openFolderModal() {
                    this.folderForm.name = '';
                    this.modals.folder = true;
                    this.refreshIcons();
                },

                createFolder() {
                    fetch(`/api/buckets/${this.currentBucket.id}/folders`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + this.adminToken
                        },
                        body: JSON.stringify({
                            name: this.folderForm.name,
                            path: this.currentPath
                        })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Create folder failed');
                        this.modals.folder = false;
                        this.showToast('success', 'Folder created!');
                        this.loadFiles();
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    });
                },

                uploadFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.performUpload(file);
                },

                handleDrop(event) {
                    this.dragover = false;
                    const files = event.dataTransfer.files;
                    if (files.length === 0) return;
                    this.performUpload(files[0]);
                },

                performUpload(file) {
                    this.showToast('info', `Uploading ${file.name}...`);
                    
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('path', this.currentPath);

                    fetch(`/api/buckets/${this.currentBucket.slug}/upload`, {
                        method: 'POST',
                        headers: { 'X-API-KEY': this.currentBucket.secretKey },
                        body: formData
                    })
                    .then(res => {
                        if (!res.ok) return res.json().then(d => { throw new Error(d.message || 'Upload failed') });
                        return res.json();
                    })
                    .then(data => {
                        this.showToast('success', `${file.name} uploaded successfully!`);
                        this.loadFiles();
                        this.loadBuckets();
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    });
                },

                deleteFile(item) {
                    if (!confirm(`Are you sure you want to delete ${item.name}?`)) return;

                    const deleteUrl = item.type === 'folder' 
                        ? `/api/files`
                        : `/api/files/${item.id}`;

                    let fetchOptions = {
                        method: 'DELETE',
                        headers: { 'Authorization': 'Bearer ' + this.adminToken }
                    };

                    if (item.type === 'folder') {
                        const relPath = base64Decode(item.id).split('/').slice(1).join('/');
                        fetchOptions.headers['X-API-KEY'] = this.currentBucket.secretKey;
                        fetchOptions.headers['Content-Type'] = 'application/json';
                        
                        fetch(`/api/files?path=${encodeURIComponent(relPath)}&type=directory`, {
                            method: 'DELETE',
                            headers: { 
                                'Authorization': 'Bearer ' + this.currentBucket.secretKey,
                                'X-Bucket': this.currentBucket.slug
                            }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Delete directory failed');
                            this.showToast('success', 'Folder deleted.');
                            this.loadFiles();
                            this.loadBuckets();
                        })
                        .catch(err => this.showToast('error', err.message));
                        return;
                    }

                    fetch(deleteUrl, fetchOptions)
                    .then(res => {
                        if (!res.ok) throw new Error('Delete failed');
                        this.showToast('success', 'Item deleted.');
                        this.loadFiles();
                        this.loadBuckets();
                    })
                    .catch(err => {
                        this.showToast('error', err.message);
                    });
                },

                showToast(type, message) {
                    const id = Date.now();
                    this.toasts.push({ id, type, message });
                    this.refreshIcons();
                    setTimeout(() => this.removeToast(id), 5000);
                },

                removeToast(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                },

                formatSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },

                copyToClipboard(text, message) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.showToast('success', message);
                    });
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            };
        }

        function ltrim(str, char) {
            if (!str) return '';
            return str.startsWith(char) ? str.slice(char.length) : str;
        }

        function base64Decode(str) {
            try {
                return atob(str);
            } catch(e) {
                return str;
            }
        }
    </script>
</body>
</html>
