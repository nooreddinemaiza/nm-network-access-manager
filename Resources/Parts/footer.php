<style>
.footer-wrap{padding:2rem 1.5rem 1.5rem;border-top:0.5px solid var(--border);display:flex;flex-direction:column;align-items:center;gap:1.25rem}
.social-row{display:flex;flex-wrap:wrap;justify-content:center;gap:.625rem}
.s-link{display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .85rem;border-radius:var(--radius);border:0.5px solid var(--border);font-size:13px;font-weight:500;color:var(--text-secondary);text-decoration:none;transition:border-color .15s,color .15s,background .15s}
.s-link:hover{color:var(--text-primary);border-color:var(--border-strong);background:var(--surface-1)}
.s-link svg{width:15px;height:15px;flex-shrink:0;opacity:.75}
.s-link:hover svg{opacity:1}
.footer-author{font-size:.75rem;color:var(--text-muted);text-align:center}
.footer-author a{color:var(--text-accent);text-decoration:none;font-weight:500}
.footer-author a:hover{text-decoration:underline}
.sombre {position: absolute;right: 10%;}
</style>

<div class="footer-wrap">
    <div class="sombre">
      <?= $view->inc('components', 'sombre.php', []); ?>
    </div>
  <span class="footer-author">
    <a href="https://nooreddinemaiza.github.io" target="_blank" rel="noopener noreferrer">NM</a>
     &copy; 2026
  </span>
</div>
