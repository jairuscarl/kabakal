</main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <!-- Brand -->
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo">
                        <img src="public/images/logo.png?v=<?php echo @filemtime('public/images/logo.png') ?: time(); ?>" alt="NetSkill" height="30" style="vertical-align: middle;" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'fas fa-network-wired\'></i><span>NetSkill</span>'">
                    </a>
                    <p class="footer-description">
                        Master computer networking with our comprehensive video tutorials and earn industry-recognized certificates.
                    </p>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/jairuscarl.E" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/jairus_carl/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/in/jairuscarl/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://www.youtube.com/@99Jairus" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="https://github.com/jairuscarl" target="_blank" rel="noopener noreferrer" aria-label="GitHub"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="tutorials.php">Tutorials</a></li>
                        <li><a href="/games/" class="footer-game-link"><i class="fas fa-gamepad"></i> Mini Games</a></li>
                        <li><a href="reviews.php">Reviews</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                
                <!-- Courses -->
                <div class="footer-links">
                    <h4>Course Categories</h4>
                    <ul>
                        <li><a href="tutorials.php?category=Networking%20Basics">Networking Basics</a></li>
                        <li><a href="tutorials.php?category=IP%20Addressing">IP Addressing</a></li>
                        <li><a href="tutorials.php?category=Cisco%20Fundamentals">Cisco Fundamentals</a></li>
                        <li><a href="tutorials.php?category=Network%20Security">Network Security</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="footer-links">
                    <h4>Contact Us</h4>
                    <ul class="contact-list">
                        <li><i class="fas fa-envelope"></i> <a href="/portfolio/" target="_blank" rel="noopener noreferrer">jairuscarlesteban@gmail.com</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="/palmaportfolio/" target="_blank" rel="noopener noreferrer">ephraimpalma100@gmail.com</a></li>
                        <li><i class="fas fa-phone"></i> +63 918 607 6497</li>
                        <li><i class="fas fa-phone"></i> +63 920 804 6147</li>
                        <li><i class="fas fa-map-marker-alt"></i> 1026 M. Bernardino Brgy. Ugong Valenzuela City</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> NetSkill. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="privacy-policy.php">Privacy Policy</a>
                    <a href="terms-of-service.php">Terms of Service</a>
                    <a href="cookie-policy.php">Cookie Policy</a>
                </div>
            </div>
        </div>
        
        <!-- Network Animation Background -->
        <div class="footer-network"></div>
    </footer>
    
    <!-- Scripts - Load Chart.js asynchronously -->
    <script src="<?php echo asset_url('public/js/main.js'); ?>"></script>
    <script>
    // Load Chart.js only when needed (on pages that use it)
    if (document.querySelector('canvas[id^="chart-"]')) {
        var chartScript = document.createElement('script');
        chartScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
        chartScript.async = true;
        document.body.appendChild(chartScript);
    }
    </script>
    
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo asset_url($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>