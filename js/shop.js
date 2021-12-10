function ShopScript()
{
    const PRODUCT_TAB_NAME_DESCRIPTION = 'description';
    const PRODUCT_TAB_NAME_SPECIFICATIONS = 'specifications';
    
    this.jqTopNavbar = $('.nav-top');
    this.jqBotNavbar = $('.nav-bot');
    this.products = "";
    this.productSort = "A to Z";
    this.productLayout = "grid";
    
    this.intTopNavBarHeight = this.jqTopNavbar.outerHeight(true);
    this.intBotNavBarHeight = this.jqBotNavbar.outerHeight(true);
    this.intNavFixedGap = 130;
    
    var self = this;
    // Window scroll event for navigation bar fixation
    $(window).scroll( function(){
        // Make navigation bar fixed after certain scrollY value has been reached
        if(window.scrollY > self.intTopNavBarHeight + self.intBotNavBarHeight + self.intNavFixedGap){
            self.jqBotNavbar.addClass('nav-fixed');
            self.jqBotNavbar.css('visibility', 'visible');
            self.jqBotNavbar.css('opacity', 1);
            $('body').css('padding-top', self.intBotNavBarHeight);
        }
        // Make top navigation bar hidden once it is scrolled down enough for it not to be seen
        else if (window.scrollY > self.intBotNavBarHeight) {
            self.jqBotNavbar.css('opacity', 0);
            self.jqBotNavbar.addClass('transition');
            self.jqBotNavbar.css('visibility', 'hidden');
        }
        // Reset top navigation bar and show it
        else if(window.scrollY <= self.intNavFixedGap){
            self.jqBotNavbar.removeClass('nav-fixed');
            $('body').css('padding-top', '0');
            self.jqBotNavbar.css('visibility', 'visible');
            self.jqBotNavbar.removeClass('transition');
            self.jqBotNavbar.css('opacity', 1);
        }
    });

    // Initialize product page events
    this.init = function(productSort = 'A to Z', productLayout = 'grid')
    {
        var self = this;
        this.productLayout = productLayout;
        this.productSort = productSort;
        // Event for selecting new product sort type
        $('.product-container #productSort').change(function(){
            self.productSort = $('#productSort option:selected').val();
            self.productSearch(false);
        });

        // Event for clicking on grid view button in product page
        $('.product-container #gridSelect').click(function(){
            if (self.productLayout !== "grid") {
                self.productLayout = "grid"
                self.productSearch(false);    
            }
        });

        // Event for clicking on list view button in product page
        $('.product-container #listSelect').click(function(){
            if (self.productLayout !== "list") {
                self.productLayout = "list"
                self.productSearch(false);    
            }
        });

        // Event for clicking on open filters button in mobile view
        $('#mob_filter_open').click(function(){
           self.showMobileFilter(); 
        });
        // Perform product search
        this.productSearch(false);
    }

    // Initializes search events
    this.initSearch = function(tabName)
    {
        // Checks if user is in product tab and checks if more than 3 cjaracters typed in search bar
        if (tabName == 'Products') {
            $('#search').keyup(function () {
                if ($('#search').val().length > 3) {
                    // Makes product search
                    self.productSearch(true);
                }
            });
        }
        // Search button clicked event
        $('#search_button').click(function(){
            // Checks if search phrase not empty
            if ($('#search').val().length > 1) {
                // If user is not in product tab then redirect to product page and mnake product search then
                if (tabName != 'Products') {
                    let searchValue = $('#search').val();
                    window.location.href = "index.php?q=" + searchValue;
                }
                self.productSearch(true);
            }
        });
    }
    // Makes AJAX call to get cart info for header cart element
    this.updateCartPreview = function()
    {
        $.ajax({
            url: "ajax/get_cart.php",
            method: "POST",
            dataType: "json",
            data: {'output' : true},
            success: function (data) {
                $('.cart-info-container .cart-count-container').text(Object.keys(data['cart_items']).length);
                $('.cart-info-container .cart-text-container').text(data['cart_total']);
            },
            error: function()
            {
                
            }
        });
    }

    // Makes AJAX call to save product and its quantity in users cart
    this.addItemToCart = function(productId, quantity)
    {
        var self = this;
        $('.toast .cart-msg').hide();
        self.cartAddLoadShow(productId);
        $.ajax({
            url: "ajax/cart_add.php",
            method: "POST",
            dataType: "json",
            data: {
            'cart_product_id' : productId,
            'cart_quantity' : quantity
            },
            success: function (data) {
                // Show toast message with result
                if (data['status'] === 'success') {
                    $('.toast .cart-success').show();
                    $('.toast').toast('show');
                    self.updateCartPreview();
                    self.cartAddLoadHide(productId);
                }
                else if (data['status'] === 'error' ) {
                    $('.toast .cart-error').show();
                    $('.toast').toast('show');
                    self.cartAddLoadHide(productId);
                }
            },
            error: function()
            {
                $('.toast .cart-error').show();
                $('.toast').toast('show');
                self.cartAddLoadShow(productId);
            }
        });
    }
    
    this.cartAddLoadShow = function(productId)
    {
        
        $('.product-container .product[' + "data-product-id=" + productId + '] .btn-add-cart .btn-text').hide();
        $('.product-container .product[' + "data-product-id=" + productId + '] .btn-add-cart .loading').show();
    }
    
    this.cartAddLoadHide = function(productId)
    {
        
        $('.product-container .product[' + "data-product-id=" + productId + '] .btn-add-cart .btn-text').show();
        $('.product-container .product[' + "data-product-id=" + productId + '] .btn-add-cart .loading').hide();
    }

    // Check if quantity input is valid when adding a product to cart
    this.validateQuantity = function(productId)
    {
        var product = this.products[productId];
        
        var quantityPickerInput =  $('.product[' + "data-product-id=" + productId + '] .quantity-container .quantity-picker-input');
        
        if (quantityPickerInput.val() > 0) {
            // Uncomment to enable warning for adding higher quantity than product inventory has
            // if (quantityPickerInput.val() > product['inventoryAmount']) {
            //     quantityPickerInput.val(product['inventoryAmount']);
            //     alert('Selected quantity cannot be ordered');
            // }
            $('.product[' + "data-product-id=" + productId + '] .total-price-text').text((product['discountPrice'] * Number(quantityPickerInput.val())).toFixed(2) + ' €');

        }
         else {
            quantityPickerInput.val(1);
        }
    }

    // Gets selected specification filters in product page
    this.getSearchFilters = function()
    {
        var filters = [];
        $('.filter-option .filter-input-button:checked').each(function(){
            if (filters[$(this).attr('name')] === undefined) {
                filters[$(this).attr('name')] = [];
            }
            filters[$(this).attr('name')].push($(this).val());
        });
        return filters;
    }

    // Sets specification filters as checked from url variables
    this.setSearchFiltersChecked = function(urlFilters)
    {
        for (var filter of urlFilters) {
            if (filter[0].length > 1) {
                // Slice is used to remove numbering from fs_X
                $('.filter-option .filter-input-button[name="' + decodeURI(filter[0].slice(0, -2))  + '"][value="' + decodeURI(filter[1]) + '"]').prop('checked', true);
            }
        }
    }

    // Removes all (GET) url parameters except for product search phrase variable
    this.clearUrlFilterParams = function()
    {
        // Constructs a new url with only search phrase variable
        let urlParams = new URLSearchParams(window.location.search);
        var newUrl = window.location.href.split('?')[0];
        if (urlParams.has('q')) {
            var searchQuestion = urlParams.get('q');
            newUrl = new URL(newUrl);
            newUrl.searchParams.set('q', encodeURI(searchQuestion));
        }
        // Sets it as the new url
        window.history.replaceState(null, null, newUrl);
    }

    // Perform a AJAX product search
    this.productSearch = function(clearUrl)
    {
        const self = this;
        // Get all selected filters
        var sideFilters = this.getSearchFilters();
        if (clearUrl) {
            this.clearUrlFilterParams();
        }
        // Sets url parameters based on checked specification filters
        const url = new URL(window.location.href);
        for (const [key, value] of Object.entries(sideFilters)) {
            let i = 1;
            for (const param of value) {
                url.searchParams.set(encodeURI(key + '_' + i), encodeURI(param));
                i++;
            }
        }
        // Sets search phrase from input, adds it to url and shows search message if needed
        if ($('#search').val().length > 0) {
            url.searchParams.set('q', encodeURI($('#search').val()));
            $('.search-results-text').text($('#search').val());
            $('.search-results').show();
        } else {
            $('.search-results').hide();
        }
        // Replace url with the new url
        window.history.replaceState(null, null, url);

        let urlParams = [];

        let searchParams = new URLSearchParams(window.location.search);
        let searchQuestion = "";
        const MAX_PARAM_COUNT = 25;

        // Gets search filters from url
        let paramCount = 0;
        for (const entry of searchParams.entries()) {
            if (entry[0] === 'q') {
                searchQuestion = entry[1];
            }
            else if (entry[0].startsWith('fs_')) {
                urlParams.push(entry);
            }
            if (paramCount >= MAX_PARAM_COUNT) {
                break;
            }
            paramCount++;
        }

        // Saves product view height so to not reduce scrollY value
        var previousHeight = $('.product-container .product-row').height();
        
        $('.product-container .product-row').hide();
        
        $('.product-container #products_loading').height(previousHeight).show();
        // Makes AJAX call to get products with filters
        $.ajax({
            url: "ajax/get_products.php",
            method: "POST",
            dataType: "json",
            data: {
            'q': searchQuestion, 
            'filterParams': urlParams, 
            'layout': self.productLayout,
            'sort' : self.productSort
            },
            success: function (data) {
                // Gets product HTML
                self.products = data['products'];
                // Set specification HTML
                if (data['spec_html'] !== '') {
                    $('.spec-filters').html(data['spec_html']).parent().addClass('d-lg-block');
                }
                else {
                    $('.spec-filters').parent().removeClass('d-lg-block');
                }
                $('.product-container .product-view-container').html(data['product_html']);
                self.setSearchEvents();
                self.setSearchFiltersChecked(urlParams);
                // Removes disabled attribute from filter elements that are already checked so user can unselect them
                $('.filter-buttons .filter-input-button:checked:disabled').prop("disabled", false);
                $('.product-container #products_loading').hide();
                $('.product-container .product-view-container').show();
            },
            error: function()
            {
                
            }
        });
    }

    // Set events after AJAX products and specification filters are created
    this.setSearchEvents = function()
    {
        var self = this;
        // Event for clicking on specification filter
        $('.filter-option .filter-input-button').unbind('click');
        $('.filter-option .filter-input-button').click(function () {
            self.productSearch(true);
        });
        // Event for clicking on hide filters on mobile
        $('#mob_filter_hide').unbind('click');
        $('#mob_filter_hide').click(function(){
           self.hideMobileFilter(); 
        });
        // Event for clicking on add product to cart button
        $('.product-container .btn-add-cart').click(function(){
            var productId = $(this).data('product');
            var quantity = $('.product[data-product-id=' + productId + '] .quantity-picker-input').val();
            self.addItemToCart(productId, quantity, this);
        });
    }

    // Show mobile menu side bar
    this.showSideBar = function()
    {
        $('.mobile-sidebar-container').css('opacity', '1');
        $('.mobile-sidebar-container').css('z-index', '9999');
        $('.mobile-sidebar-container .mobile-sidebar-content').css('left', '0');
        $('body').css('overflow', 'hidden');
    }
    
    this.hideSideBar = function()
    {
        $('.mobile-sidebar-container').css('opacity', '0');
        $('.mobile-sidebar-container').css('z-index', '-1');
        $('.mobile-sidebar-container .mobile-sidebar-content').css('left', '-100%');
        $('body').css('overflow', '');
    }

    // Show or hide mobile search field
    this.mobSearch = function()
    {
        if ($('.nav-bot .search-button-mobile i').hasClass('fa-search')) {
            $('.nav-bot .logo-col').addClass('d-none');
            $('.nav-bot .search-col').removeClass('col-2').addClass('col-9');
            $('.nav-bot .search-container').removeClass('ps-2').removeClass('pe-4').removeClass('d-none').css('width', '90%');
            $('.nav-bot .search-button-mobile i').removeClass('fa-search').addClass('fa-times');
        }
        else {
            $('.nav-bot .logo-col').removeClass('d-none');
            $('.nav-bot .search-col').addClass('col-2').removeClass('col-9');
            $('.nav-bot .search-container').addClass('ps-2').addClass('pe-4').addClass('d-none').css('width', '50%');
            $('.nav-bot .search-button-mobile i').addClass('fa-search').removeClass('fa-times');
        }
    }
    
    this.showMobileFilter = function()
    {
        $('.filter-container').removeClass('d-none').addClass('d-block');
        $('body').css('overflow', 'hidden');
    }
    
    this.hideMobileFilter = function()
    {
        $('.filter-container').addClass('d-none').removeClass('d-block');
        $('body').css('overflow', '');
    }

    // Show sign up section in login/sign up form
    this.showSignUpForm = function()
    {
        $('#authModal .forgot-pass-container').hide();
        $('#authModal .form-pass-repeat').removeClass('d-none');
        $('#authModal .form-signup-option').html('<span>Already a member? <a onclick="objShop.showLoginForm(); objShop.hideAuthModalErrors();" class="link-primary">Log in now</a></span>');
        $('#authModal .form-submit button').text('Sign up');
        $('#authModal .modal-title').text('Sign up');
        $('#login_form').attr('action', 'register.php');
    }

    // Show login section in login/sign up form
    this.showLoginForm = function()
    {
        $('#authModal .forgot-pass-container').show();
        $('#authModal .form-pass-repeat').addClass('d-none');
        $('#authModal .form-signup-option').html('<span>Not a member? <a onclick="objShop.showSignUpForm(); objShop.hideAuthModalErrors();" class="link-primary">Sign up now</a></span>');
        $('#authModal .form-submit button').text('Login');
        $('#authModal .modal-title').text('Sign in');
        $('#login_form').attr('action', 'login.php');
    }

    // Remove all errors from login/signup form
    this.hideAuthModalErrors = function()
    {
        $('#authModal .login-gn-error').removeClass('visible');
        $('#authModal .login-gn-error').addClass('invisible');
        $('#authModal input').removeClass('is-invalid');
    }
    
    this.openCartAddModal = function()
    {
        $('#cartAddModal').modal('show');
    }

    // Open and show specific product modal with additional information about product
    this.openProductModal = function(productId, tab = PRODUCT_TAB_NAME_DESCRIPTION)
    {
        var product = this.products[productId];
        
        // Change HTML to match opened product
        var imageCarouselHtml = '<div id="carouselProductPhotos" class="carousel carousel-dark slide" data-bs-ride="carousel">' +
         '<div class="carousel-indicators">';
         var photoCnt = product['photos'].length;
         for (var i = 0; i < photoCnt; i++) {
            if (i === 0) {
                imageCarouselHtml += '<button type="button" data-bs-target="#carouselProductPhotos" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>';
            } else {
                imageCarouselHtml += '<button type="button" data-bs-target="#carouselProductPhotos" data-bs-slide-to="'+i+'" aria-label="Slide '+i+'"></button>';
            }
         }
         imageCarouselHtml += '</div><div class="carousel-inner">' +
          '<div class="carousel-item active">' +
           '<img class="d-block w-100" src="images/'+product['photoPath']+'" alt="Product image"></div>';
         i = 1;
         for (var photo of product['photos']) {
            if (i !== 1) {
                imageCarouselHtml += '<div class="carousel-item">' +
                    '<img class="d-block w-100" src="images/' + photo + '" alt="Product image"></div>';
            }
            i++;
         }
         imageCarouselHtml += '</div>';
         imageCarouselHtml += '<button type="button" class="carousel-control-prev" data-bs-target="#carouselProductPhotos"data-bs-slide="prev">' +
             '<span class="carousel-control-prev-icon" aria-hidden="true"></span>' +
             '<span class="visually-hidden">Previous</span>' +
             '</button>' +
             '<button type="button" class="carousel-control-next" data-bs-target="#carouselProductPhotos" data-bs-slide="next">' +
             '<span class="carousel-control-next-icon" aria-hidden="true"></span>' +
             '<span class="visually-hidden">Next</span>' +
             '</button>' +
             '</div>';
        
        $('#productModal .product-images').html(imageCarouselHtml);
        $('#productModal .product-images-mobile').html(imageCarouselHtml);
        $('#productModal .product-images-mobile [data-bs-target="#carouselProductPhotos"]').attr('data-bs-target', "#carouselProductPhotosMobile");
        $('#productModal .product-images-mobile #carouselProductPhotos').attr("id", "carouselProductPhotosMobile");
        
        //$('#productModal .product-images').html('<img src="images/' + product['photoPath'] + '" class="card-img-top float-start" alt="Product photo">');
        $('#productModal .card-subtitle').html(product['category']);
        $('#productModal .card-title').html(product['name']);

        // Check if discount must be shown for this product
        if (product['discountPercent'] > 0)
        {
            $('#productModal .retail-price-default').addClass('price-sale');
            $('#productModal .price-new').text(product['discountPrice'] + ' €');
            $('#productModal .price-new').show();
        }
        else
        {
            $('#productModal .retail-price-default').removeClass('price-sale');
            $('#productModal .price-new').hide();
        }
        
        $('#productModal .retail-price-default').html(product['price'] + ' €');
        // Set description
        if (product['description'] == null) {
            $('#productModal .product-description').html("Product has no description");
        }
        else {
            $('#productModal .product-description').html(product['description']);
        }
        
        var specificationHtml = '';

        // Create HTML for product specifications
        for (var spec in product['specifications'])
        {
            specificationHtml += '<tr>';
            specificationHtml += '<th scope="row">' + spec + '</th>';
            specificationHtml += '<td>' + product['specifications'][spec] + '</td>';
            specificationHtml += '</tr>';
        }
        
        $('#productModal .product-specification-table').html(specificationHtml);
        // Show one of the product modal tabs (Description or specifications)
        if (tab === PRODUCT_TAB_NAME_SPECIFICATIONS) {
            $('#productModal .product-specifications').show();
            $('#productModal .product-description').hide();
            $('#productModal .page-switch-container button').removeClass('active');
            $('#productModal .page-switch-container button[data-tab="' + PRODUCT_TAB_NAME_SPECIFICATIONS + '"]').addClass('active');
        } 
        else 
        {
            $('#productModal .product-specifications').hide();
            $('#productModal .product-description').show();
            $('#productModal .page-switch-container button').removeClass('active');
            $('#productModal .page-switch-container button[data-tab="' + PRODUCT_TAB_NAME_DESCRIPTION + '"]').addClass('active');
            
        }
    }

    // Change product modal tab to a new one (description or specifications)
    this.changeProductModalTab = function(clickedButton)
    {
        var jqClickedTab = $(clickedButton);
        $('#productModal .page-switch-container button').removeClass('active');
        $('#productModal .product-tab').hide();
        
        jqClickedTab.addClass('active');
        $('#productModal .product-tab.product-' + jqClickedTab.data('tab')).show();
    }

    // Add or decrease quantity picker amount when clicking on "+" or "-" buttons
    this.changeQuantityPickerAmount = function(add, productId)
    {
        var product = this.products[productId];
        
        var quantityPickerInput =  $('.quantity-picker-input[' + "data-product-id=" + productId + ']');
        
        if (add === 1) {
            quantityPickerInput[0].stepUp();
        }
        else if (add === 0) {
            quantityPickerInput[0].stepDown();
        }
        
        if (quantityPickerInput.val() > 0) {
            $('.product[' + "data-product-id=" + productId + '] .total-price-text').text((product['discountPrice'] * Number(quantityPickerInput.val())).toFixed(2) + ' €');
        }
    }
}