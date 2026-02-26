<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-3 footer-col">
                <h4>Treakers</h4>
                <ul>
                    <li><a href="/other_pages/AboutUsPage/aboutus.php">about us</a></li>
                    <li><a href="/other_pages/ProductPage/allproductsuser.php">our products</a></li>
                    <li><a href="#">privacy policy</a></li>
                </ul>
            </div>
            <div class="col-md-3 footer-col">
                <h4>get help</h4>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="/other_pages/ContactUsPage/contactus.php">Contact Us</a></li>
                    <li><a href="#">returns</a></li>
                    <li><a href="/other_pages/BasketPage/cart.php">Basket</a></li>
                </ul>
            </div>
            <div class="col-md-3 footer-col">
                <h4>online shop</h4>
                <ul>
                    <li><a href="/other_pages/ProductPage/allproductsuser.php">Sneakers</a></li>
                    <li><a href="/other_pages/ProductPage/allproductsuser.php">Trainers</a></li>
                </ul>
            </div>
            <div class="col-md-3 footer-col">
                <h4>follow us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>
<script>
    // JavaScript function to show the pop-up modal
    function showReviewPopup() {
        document.getElementById("reviewModal").style.display = "block";
    }

    // JavaScript function to hide the pop-up modal
    function hideReviewPopup() {
        document.getElementById("reviewModal").style.display = "none";
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(isset($_SESSION['message'])): ?>
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: '<?php echo $_SESSION['alert_type']; ?>',
                title: '<?php echo $_SESSION['message']; ?>',
                showConfirmButton: false,
                timer: 1500
            });
        }
        <?php unset($_SESSION['message']); ?>
        <?php unset($_SESSION['alert_type']); ?>
        <?php endif; ?>
    });
</script>
