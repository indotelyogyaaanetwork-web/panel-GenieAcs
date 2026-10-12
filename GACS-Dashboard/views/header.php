<?php /* butuh $title dan $active */
$menu = ['dashboard' => ['Dashboard', 'fa-gauge-high'], 'devices' => ['Perangkat', 'fa-server'], 'map' => ['Peta', 'fa-diagram-project'],
         'configuration' => ['Konfigurasi', 'fa-gear'], 'view-logs' => ['Log', 'fa-clock-rotate-left'], 'logout' => ['Keluar', 'fa-arrow-right-from-bracket']]; ?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= esc(csrf()) ?>"><title><?= esc($title) ?> - GACS Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/style.css"></head>
<body class="text-slate-800">
<aside id="side" class="fixed inset-y-0 left-0 z-40 w-[200px] bg-[#1b2a4a] text-white -translate-x-full lg:translate-x-0 transition-transform">
  <div class="h-[103px] flex flex-col items-center justify-center border-b border-white/10"><i class="fa-solid fa-network-wired text-3xl mb-2"></i><b class="text-sm">GACS Dashboard</b></div>
  <?php foreach ($menu as $k => [$l, $i]): ?>
    <a href="<?= $k ?>.php" class="flex items-center gap-3 h-[47px] px-4 text-[13px] font-semibold border-l-[3px] hover:bg-[#2b3b5f] <?= $k === $active ? 'bg-[#2b3b5f] border-indigo-500' : 'border-transparent' ?>"><i class="fa-solid <?= $i ?> w-5 text-center"></i><?= $l ?></a>
  <?php endforeach; ?>
</aside>
<div class="lg:ml-[200px]">
  <header class="bg-white h-[50px] border-b flex items-center justify-between px-5 sticky top-0 z-20">
    <div class="flex items-center gap-3"><button class="lg:hidden" onclick="side.classList.toggle('-translate-x-full')" aria-label="Menu"><i class="fa-solid fa-bars"></i></button><h1 class="text-xl font-medium"><?= esc($title) ?></h1></div>
    <span class="text-sm"><i class="fa-regular fa-circle-user"></i> <?= esc($_SESSION['user']) ?></span>
  </header>
  <main class="p-5 min-h-[calc(100vh-108px)]">
