<?php if(isLoggedIn()): ?>
    <footer class="dashboard-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> Powered by LGK Tech Solutions</p>
            <div class="footer-links">
                <a href="<?php echo BASE_URL; ?>pages/settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="<?php echo BASE_URL; ?>pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </footer>
<?php else: ?>
    <footer class="login-footer">
        <p>&copy; <?php echo date('Y'); ?> KNP Dating Site - Powered by LGK Tech Solutions</p>
    </footer>
<?php endif; ?>
</main>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>