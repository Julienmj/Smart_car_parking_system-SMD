    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">Smart Parking System</h3>
                    <p class="footer-text">Modern, efficient parking management solution for smart cities.</p>
                    <div class="footer-stats">
                        <div class="stat-item">
                            <span class="stat-number" id="totalSlots">0</span>
                            <span class="stat-label">Total Slots</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" id="availableSlots">0</span>
                            <span class="stat-label">Available</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-subtitle">Quick Links</h4>
                    <ul class="footer-links">
                        <?php if (isLoggedIn()): ?>
                            <?php if (isAdmin()): ?>
                                <li><a href="<?php echo $BASE; ?>dashboard-admin.php">Admin Dashboard</a></li>
                                <li><a href="<?php echo $BASE; ?>admin/manage-slots.php">Manage Slots</a></li>
                                <li><a href="<?php echo $BASE; ?>admin/view-sessions.php">View Sessions</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo $BASE; ?>dashboard-client.php">Client Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo $BASE; ?>logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $BASE; ?>index.html">Home</a></li>
                            <li><a href="<?php echo $BASE; ?>login.php">Login</a></li>
                            <li><a href="<?php echo $BASE; ?>register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-subtitle">Features</h4>
                    <ul class="footer-links">
                        <li>Real-time Slot Availability</li>
                        <li>Automated Fee Calculation</li>
                        <li>Multi-floor Parking Support</li>
                        <li>VIP & Disabled Access</li>
                        <li>Mobile Responsive Design</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-subtitle">Contact</h4>
                    <div class="footer-contact">
                        <p>📧 support@smartparking.com</p>
                        <p>📞 +250 788 123 456</p>
                        <p>📍 Kigali, Rwanda</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Smart Parking System. All rights reserved.</p>
                <p>Built with ❤️ for smarter parking solutions</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo $BASE; ?>assets/js/main.js"></script>
    <?php if (isset($adminPage)): ?>
    <script src="<?php echo $BASE; ?>admin/admin-scripts.js"></script>
    <?php endif; ?>
    
    <?php if (isLoggedIn()): ?>
    <script>
        function updateFooterStats() {
            fetch('<?php echo $BASE; ?>api/get-slot-status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        document.getElementById('totalSlots').textContent = data.data.statistics.total_slots || 0;
                        document.getElementById('availableSlots').textContent = data.data.statistics.available_slots || 0;
                    }
                })
                .catch(() => {});
        }
        updateFooterStats();
        setInterval(updateFooterStats, 30000);
    </script>
    <?php endif; ?>

</body>
</html>
