<?php
session_start();
require_once __DIR__ . '/Assets/Functions/myfunctions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Treakers | Home Page</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700">
    <link id="pagestyle" href="Assets/CSS/material-dashboard.min.css" rel="stylesheet">
    <link href="Assets/CSS/nucleo-icons.css" rel="stylesheet">
    <link href="Assets/CSS/nucleo-svg.css" rel="stylesheet">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="Assets/CSS/HomePage.css">
    <link rel="stylesheet" href="Assets/CSS/nav.css">

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body class="g-sidenav-show  bg-gray-200">
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include __DIR__ . '/Includes/nav.php'; ?>

    <section>
      <div class="bg" style="background-image: url('Assets/Images/mainwallpaper.jpg')"></div>
      <h1 class="fs-1 bg-gradient-faded-dark-blue text-white rounded mr-0 p-2">Treakers</h1>
      <h1 class="bg-gradient-faded-primary text-white p-2 rounded">Embrace your UrbanSole!</h1>
    </section>

    <section class="trending-section bg-gradient-faded-white">
        <h1 class="fs-2 text-black rounded text-center">Trending Products</h1>
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php
                $trendingProducts = getAllTrending();
                foreach ($trendingProducts as $product): ?>
                    <div class="swiper-slide" style="background: url('<?= htmlspecialchars(assetImageSrc((string)$product['image'], 'product'), ENT_QUOTES) ?>') no-repeat 50% 50% / cover;">
                        <div>
                            <h2 class="text-white bg-gradient-faded-dark rounded p-2"><?php echo $product['name']; ?></h2>
                            <a href="other_pages/ProductPage/view_product.php?product=<?php echo $product['slug']; ?>">Buy Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>


    <section>
        <div class="bg" style="background-image: url('Assets/Images/shoe slant.jpg')"></div>
        <h1 class="bg-gradient-faded-primary text-white p-2 rounded">Join us today</h1>
    </section>

    <?php include __DIR__ . '/Includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="Assets/JS/HomePage.js"></script>
    <script src="Assets/JS/custom.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>
        <script>
            $(document).ready(function() {
                // Function to perform search
                function performSearch() {
                    var searchQuery = $('.search-input').val();
                    if (searchQuery.length > 2) {
                        $.ajax({
                            url: '/Assets/Functions/search_handler.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {searchQuery: searchQuery},
                            success: function(products) {
                                var suggestions = products.map(product => {
                                    const rawImage = (product.image || '').toString();
                                    const imgSrc = (rawImage.startsWith('http://') || rawImage.startsWith('https://') || rawImage.startsWith('/'))
                                        ? rawImage
                                        : `/Assets/Images/Product_Images/${rawImage}`;
                                    return `<div class='suggestion-item' data-slug='${product.slug}'>
            <img src='${imgSrc}' class='suggestion-image'>
            <div class='suggestion-details'>
                <span class='suggestion-name'>${product.name}</span>
                <span class='suggestion-price'>£: ${product.discounted_price}</span>
            </div>
        </div>`;
                                }).join('');
                                $('.search-suggestions').html(suggestions).show();
                            },
                        });
                    } else {
                        $('.search-suggestions').hide();
                    }
                }

                // Event listener for search input
                $('.search-input').on('input', function() {
                    performSearch();
                });

                // Event listener for search button
                $('.search-button').on('click', function(e) {
                    e.preventDefault(); // Prevent the form from submitting through the browser
                    performSearch();
                });

                // Event listener for clicking on a suggestion
                $(document).on('click', '.suggestion-item', function() {
                    var slug = $(this).data('slug');
                    window.location.href = `/other_pages/ProductPage/view_product.php?product=${slug}`;
                });
            });
        </script>




</body>
</html>
