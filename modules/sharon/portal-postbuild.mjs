// Menambahkan tema seragam SIMRS ke hasil build (dist/index.html).
import { readFileSync, writeFileSync } from 'node:fs'
const f = new URL('./dist/index.html', import.meta.url)
let s = readFileSync(f, 'utf8')
if (!s.includes('simrs-module-theme')) {
  s = s.replace('</head>', `<!-- SIMRS: tema seragam portal -->
<script>document.documentElement.dataset.simrsMod="sharon";if(window.top!==window.self)document.documentElement.classList.add("embedded-simrs-module");</script>
<link rel="stylesheet" id="simrs-module-theme" href="../../../assets/module-theme.css">
</head>`)
  s = s.replace(/<title>.*?<\/title>/, '<title>SIMRS RS Suka Sehat</title>')
  s = s.replace('lang="<!-- figma:lang -->"', 'lang="id"')
  writeFileSync(f, s)
}
console.log('dist/index.html siap dipakai portal')
