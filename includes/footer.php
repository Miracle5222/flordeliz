    </main>
    <footer class="site-footer">
    <p>&copy; Flor de Liz <?php echo date('Y'); ?> · All rights reserved.</p>
  </footer>
</div>
</div>
<script>
// Sidebar toggle
var sidebarToggle = document.getElementById('sidebarToggle');
var sidebarClose = document.getElementById('sidebarClose');
var sidebar = document.getElementById('sidebar');
if(sidebarToggle) sidebarToggle.addEventListener('click', function(){ sidebar.classList.toggle('open'); });
if(sidebarClose) sidebarClose.addEventListener('click', function(){ sidebar.classList.remove('open'); });
// Close sidebar on nav link click (mobile)
document.querySelectorAll('.sidebar-nav a').forEach(function(a){ a.addEventListener('click', function(){ sidebar.classList.remove('open'); }); });
</html>
