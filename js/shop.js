function ShopScript()
{
    const PRODUCT_TAB_NAME_DESCRIPTION = 'description';
    const PRODUCT_TAB_NAME_SPECIFICATIONS = 'specifications';
    
    this.jqTopNavbar = $('.nav-top');
    this.jqBotNavbar = $('.nav-bot');
    this.products = "";
    
    this.intTopNavBarHeight = this.jqTopNavbar.outerHeight(true);
    
    
    var self = this;
    $(window).scroll( function(){
        if(window.scrollY > self.intTopNavBarHeight){
            self.jqBotNavbar.addClass('nav-fixed')
            $('body').css('padding-top', self.jqBotNavbar.outerHeight(true));
        }
        else if(window.scrollY < self.intTopNavBarHeight){
            self.jqBotNavbar.removeClass('nav-fixed');
            $('body').css('padding-top', '0');
        }
    });
    
    $()
    
    this.init = function()
    {
        this.productSearch(false);
    }
    
    this.validateQuantity = function(productId)
    {
        var product = this.products[productId];
        
        var quantityPickerInput =  $('.card[' + "data-product-id=" + productId + '] .quantity-container .quantity-picker-input');
        
        if (quantityPickerInput.val() > 0) {
            if (quantityPickerInput.val() > product['inventoryAmount']) {
                quantityPickerInput.val(product['inventoryAmount']);
                alert('Selected quantity cannot be ordered');
            }
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price').css('visibility', 'visible');
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price .total-price-text').text((product['discountPrice'] * Number(quantityPickerInput.val())).toFixed(2));

            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price .btn-add-cart').css('display', 'block');
        } else {
            $('.quantity-container .total-price').css('visibility', 'hidden');
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price .btn-add-cart').hide();
        }
    }
    
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
    
    this.setSearchFiltersChecked = function(urlFilters)
    {
        for (var filter of urlFilters) {
            if (filter[0].length > 1) {
                console.log(filter[0].slice(0, -2), filter[1]);
                // Slice is used to remove numbering from fs_X
                $('.filter-option .filter-input-button[name="' + filter[0].slice(0, -2)  + '"][value="' + decodeURI(filter[1]) + '"]').prop('checked', true);
                console.log($('.filter-option .filter-input-button[name="' + filter[0].slice(0, -2)  + '"][value="' + decodeURI(filter[1]) + '"]'));
            }
        }
    }
    
    this.clearUrlFilterParams = function()
    {
        let urlParams = new URLSearchParams(window.location.search);
        var newUrl = window.location.href.split('?')[0];
        if (urlParams.has('q')) {
            var searchQuestion = urlParams.get('q');
            newUrl = new URL(newUrl);
            newUrl.searchParams.set('q', encodeURI(searchQuestion));
        }
        window.history.replaceState(null, null, newUrl);
    }
    
    this.productSearch = function(clearUrl)
    {
        const self = this;
        
        var sideFilters = this.getSearchFilters();
        if (clearUrl) {
            this.clearUrlFilterParams();
        }
        const url = new URL(window.location.href);
        for (const [key, value] of Object.entries(sideFilters)) {
            let i = 1;
            for (const param of value) {
                url.searchParams.set(encodeURI(key + '_' + i), encodeURI(param));
                i++;
            }
        }
        //url.searchParams.set('param1', 'val1');
        window.history.replaceState(null, null, url);

        let urlParams = [];

        let searchParams = new URLSearchParams(window.location.search);
        let searchQuestion = "";
        const MAX_PARAM_COUNT = 25;

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
        $('.product-container .row').hide();
        $('.product-container #products_loading').show();
        
        $.ajax({
            url: "get_products.php",
            method: "POST",
            dataType: "json",
            data: {'q': searchQuestion, 'filterParams': urlParams},
            success: function (data) {
                console.log(data);
                self.products = data['products'];
                $('.spec-filters').html(data['spec_html']);
                $('.product-container .row').html(data['product_html']);
                $('.filter-option .filter-input-button').click(function () {
                    self.productSearch(true);
                });
                self.setSearchFiltersChecked(urlParams);
                $('.product-container #products_loading').hide();
                $('.product-container .row').show();
            },
            error: function()
            {
                
            }
        });
    }
        
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
    
    this.mobSearch = function()
    {
        if ($('.nav-bot .search-button-mobile i').hasClass('fa-search')) {
            $('.nav-bot .navbar-brand').addClass('d-none');
            $('.nav-bot .mobile-sidebar-toggle').addClass('d-none');
            $('.nav-bot .search-container').removeClass('ps-2').removeClass('pe-4').removeClass('d-none').css('width', '65%');
            $('.nav-bot .search-button-mobile i').removeClass('fa-search').addClass('fa-times');
        }
        else {
            $('.nav-bot .navbar-brand').removeClass('d-none');
            $('.nav-bot .mobile-sidebar-toggle').removeClass('d-none');
            $('.nav-bot .search-container').addClass('ps-2').addClass('pe-4').addClass('d-none').css('width', '50%');
            $('.nav-bot .search-button-mobile i').addClass('fa-search').removeClass('fa-times');
        }
    }
    
    this.showSignUpForm = function()
    {
        $('#authModal .forgot-pass-container').hide();
        $('#authModal .form-pass-repeat').removeClass('d-none');
        $('#authModal .form-signup-option').html('<span>Already a member? <a onclick="objShop.showLoginForm(); objShop.hideAuthModalErrors();" class="link-primary">Log in now</a></span>');
        $('#authModal .form-submit button').text('Sign up');
        $('#authModal .modal-title').text('Sign up');
        $('#login_form').attr('action', 'register_new.php');
    }
    
    this.showLoginForm = function()
    {
        $('#authModal .forgot-pass-container').show();
        $('#authModal .form-pass-repeat').addClass('d-none');
        $('#authModal .form-signup-option').html('<span>Not a member? <a onclick="objShop.showSignUpForm(); objShop.hideAuthModalErrors();" class="link-primary">Sign up now</a></span>');
        $('#authModal .form-submit button').text('Login');
        $('#authModal .modal-title').text('Sign in');
        $('#login_form').attr('action', 'login.php');
    }
    
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
    
    this.openProductModal = function(productId, tab = PRODUCT_TAB_NAME_DESCRIPTION)
    {
        // TODO : Add support for more images (Carousel)
        var product = this.products[productId];
        
        $('#productModal .product-images').html('<img src="test_images/' + product['photoPath'] + '" class="card-img-top float-start" alt="Product photo">');
        $('#productModal .card-subtitle').html(product['category']);
        $('#productModal .card-title').html(product['name']);
        
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
        
        if (product['description'] == null) {
            $('#productModal .product-description').html("Product has no description");
        }
        else {
            $('#productModal .product-description').html(product['description']);
        }
        
        var specificationHtml = '';
        
        for (var spec in product['specifications'])
        {
            specificationHtml += '<tr>';
            specificationHtml += '<th scope="row">' + spec + '</th>';
            specificationHtml += '<td>' + product['specifications'][spec] + '</td>';
            specificationHtml += '</tr>';
        }
        
        $('#productModal .product-specification-table').html(specificationHtml);
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
    
    this.changeProductModalTab = function(clickedButton)
    {
        var jqClickedTab = $(clickedButton);
        $('#productModal .page-switch-container button').removeClass('active');
        $('#productModal .product-tab').hide();
        
        jqClickedTab.addClass('active');
        $('#productModal .product-tab.product-' + jqClickedTab.data('tab')).show();
    }
    
    this.changeQuantityPickerAmount = function(add, productId)
    {
        // TODO : Check for currency rounding errors
        var product = this.products[productId];
        
        var quantityPickerInput =  $('.card[' + "data-product-id=" + productId + '] .quantity-container .quantity-picker-input');
        if (add === 1) {
            quantityPickerInput[0].stepUp();
        }
        else if (add === 0) {
            quantityPickerInput[0].stepDown();
        }
        
        if (quantityPickerInput.val() > 0) {
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price').css('visibility', 'visible');
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price .total-price-text').text((product['discountPrice'] * Number(quantityPickerInput.val())).toFixed(2));
            
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price .btn-add-cart').css('display', 'block');
        }
        else {
            $('.quantity-container .total-price').css('visibility', 'hidden');
            $('.card[' + "data-product-id=" + productId + '] .quantity-container .total-price .btn-add-cart').hide();
        }
    }
}