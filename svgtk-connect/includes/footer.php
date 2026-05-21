    </main>
    <footer style="padding:var(--space-4) var(--space-8);border-top:1px solid var(--color-border);font-size:var(--text-xs);color:var(--color-text-muted);text-align:center;">
      © <?= date('Y') ?> СВГТК Портал — Информационная система достижений
    </footer>
  </div>
</div>
<script>
const sidebar = document.getElementById('sidebar');
const hamburger = document.getElementById('hamburger');
if (hamburger) hamburger.addEventListener('click', () => sidebar.classList.toggle('open'));
document.addEventListener('click', e => {
  if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== hamburger) {
    sidebar.classList.remove('open');
  }
});
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const container = btn.closest('[data-tabs]') || document;
    container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const target = document.getElementById(btn.dataset.tab);
    if (target) target.classList.add('active');
  });
});
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
});
</script>
</body>
</html>