<?php
/**
 * Admin Footer Include
 * 
 * Common footer for all admin pages
 * 
 * Developer: Benjamin NIYOMURINZI
 */
?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="background: white; padding: 20px 0; margin-top: 50px; box-shadow: 0 -2px 10px rgba(0,0,0,0.08);">
        <div class="container-fluid">
            <div class="text-center text-muted">
                <p style="margin: 0;">
                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. 
                    <?php echo $lang === 'english' ? 'All rights reserved' : 'Uburenganzira bwose burarinzwe'; ?>.
                </p>
                <p style="margin: 5px 0 0 0; font-size: 12px;">
                    <?php echo $lang === 'english' ? 'Developed by Benjamin NIYOMURINZI' : 'Byakozwe na Benjamin NIYOMURINZI'; ?>
                </p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>