
/* =========================================
   01. LUCIDE ICONS
========================================= */

document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) {
    lucide.createIcons();
  }
});


/* =========================================
   02. PAGE LOADER
========================================= */

(() => {
  const loader = document.getElementById("pageLoader");

  if (!loader) return;

  document.body.classList.add("loading");

  const hideLoader = () => {
    loader.classList.add("loaded");
    document.body.classList.remove("loading");
  };

  window.addEventListener("load", hideLoader);

  if (document.readyState === "complete") {
    hideLoader();
  }
})();


// Annoucement Slider
document.addEventListener("DOMContentLoaded", function () {
  const messages = document.querySelectorAll(".announcement-slider p");
  let current = 0;

  setInterval(function () {
    messages[current].classList.remove("active");

    current = (current + 1) % messages.length;

    messages[current].classList.add("active");
  }, 3000);
});




/* =========================================
   03. SMART STICKY HEADER
========================================= */

(() => {

  const header = document.getElementById("siteHeader");

  if (!header) return;

  let lastScroll = window.scrollY;
  let ticking = false;

  const updateHeader = () => {

    const currentScroll = window.scrollY;
    const scrollingUp = currentScroll < lastScroll;

    // Don't hide header while mobile menu is open
    const menuOpen = document.getElementById("mainNav")
      ?.classList.contains("active");

    if (menuOpen) {
      header.classList.remove("header-hidden");
      lastScroll = currentScroll;
      ticking = false;
      return;
    }

    if (currentScroll > 120) {

      header.classList.add("header-scrolled");

      if (scrollingUp) {
        header.classList.remove("header-hidden");
      } else {
        header.classList.add("header-hidden");
      }

    } else {

      header.classList.remove(
        "header-scrolled",
        "header-hidden"
      );

    }

    lastScroll = currentScroll;
    ticking = false;

  };

  window.addEventListener("scroll", () => {

    if (!ticking) {

      window.requestAnimationFrame(updateHeader);
      ticking = true;

    }

  }, { passive: true });

  updateHeader();

})();


/* =========================================
   04. MOBILE NAVIGATION
========================================= */

(() => {

  const menuToggle = document.getElementById("menuToggle");
  const menuClose = document.getElementById("menuClose");
  const mainNav = document.getElementById("mainNav");
  const navOverlay = document.getElementById("navOverlay");

  if (!menuToggle || !mainNav || !navOverlay) return;

  const navLinks = mainNav.querySelectorAll(
    'a.nav-link, .nav-submenu a'
  );

  // Refresh Lucide Icons
  const refreshIcons = () => {

    if (window.lucide) {
      window.lucide.createIcons();
    }

  };


  // OPEN MENU
  const openMenu = () => {

    mainNav.classList.add("active");
    navOverlay.classList.add("active");

    menuToggle.setAttribute("aria-expanded", "true");
    menuToggle.setAttribute("aria-label", "Close navigation");

    menuToggle.innerHTML = '<i data-lucide="x"></i>';

    document.body.style.overflow = "hidden";

    document.querySelectorAll(".nav-dropdown").forEach(dropdown => {
      dropdown.classList.remove("open");

      const button = dropdown.querySelector(".nav-dropdown-toggle");

      if (button) {
        button.setAttribute("aria-expanded", "false");
      }
    });

    headerVisibility();

    refreshIcons();

  };


  // CLOSE MENU
  const closeMenu = () => {

    mainNav.classList.remove("active");
    navOverlay.classList.remove("active");

    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Open navigation");

    menuToggle.innerHTML = '<i data-lucide="menu"></i>';

    document.body.style.overflow = "";

    headerVisibility();

    refreshIcons();

  };


  // Restore Header Visibility
  function headerVisibility() {

    const header = document.getElementById("siteHeader");

    if (header && !mainNav.classList.contains("active")) {
      header.classList.remove("header-hidden");
    }

  }


  // Hamburger Toggle
  menuToggle.addEventListener("click", () => {

    if (mainNav.classList.contains("active")) {
      closeMenu();
    } else {
      openMenu();
    }

  });


  // Close Button
  if (menuClose) {

    menuClose.addEventListener("click", closeMenu);

  }


  // Overlay Click
  navOverlay.addEventListener("click", closeMenu);


  // Navigation Links
  navLinks.forEach(link => {

    link.addEventListener("click", () => {

      closeMenu();

    });

  });


  // Escape Key
  document.addEventListener("keydown", event => {

    if (event.key === "Escape") {
      closeMenu();
    }

  });


  // Close Menu on Desktop Resize
  window.addEventListener("resize", () => {

    if (window.innerWidth > 991) {
      closeMenu();
    }

  });


  /* =====================================
     05. MOBILE DROPDOWN
  ===================================== */

  const dropdownButtons = mainNav.querySelectorAll(
    ".nav-dropdown-toggle"
  );

  dropdownButtons.forEach(button => {

    button.addEventListener("click", () => {

      const currentDropdown = button.closest(".nav-dropdown");

      if (!currentDropdown) return;

      const isOpen = currentDropdown.classList.contains("open");

      // Close other dropdowns
      mainNav.querySelectorAll(".nav-dropdown").forEach(dropdown => {

        dropdown.classList.remove("open");

        const dropdownButton = dropdown.querySelector(
          ".nav-dropdown-toggle"
        );

        if (dropdownButton) {
          dropdownButton.setAttribute(
            "aria-expanded",
            "false"
          );
        }

      });

      // Toggle current dropdown
      if (!isOpen) {

        currentDropdown.classList.add("open");

        button.setAttribute("aria-expanded", "true");

      }

      refreshIcons();

    });

  });


  // Initial Icons
  refreshIcons();

})();



/* =========================================
   05. PREMIUM SEARCH PANEL
========================================= */

(() => {

  const searchToggle = document.getElementById("searchToggle");
  const searchPanel = document.getElementById("searchPanel");
  const searchClose = document.getElementById("searchClose");
  const searchInput = document.getElementById("perfumeSearch");
  const searchClear = document.getElementById("searchClear");
  const productGrid = document.getElementById("searchProductGrid");
  const resultsHeading = document.getElementById("resultsHeading");
  const resultsCount = document.getElementById("resultsCount");
  const noResults = document.getElementById("searchNoResults");
  const trendingSection = document.getElementById("trendingSection");

  if (
    !searchToggle || !searchPanel || !searchClose ||
    !searchInput || !productGrid
  ) return;

  // Collect existing website products
  const products = [];
  const seen = new Set();

  document.querySelectorAll(".product-card").forEach(card => {

    const name = card.querySelector(".product-info h3");
    const image = card.querySelector(".product-image img");
    const price = card.querySelector(".product-price strong");
    const oldPrice = card.querySelector(".product-price del");
    const discount = card.querySelector(".product-price span");
    const meta = card.querySelector(".product-meta");

    if (!name || !image) return;

    const title = name.textContent.trim();

    if (!title || seen.has(title.toLowerCase())) return;

    seen.add(title.toLowerCase());

    products.push({
      title,
      image: image.getAttribute("src") || image.src,
      price: price?.textContent.trim() || "",
      oldPrice: oldPrice?.textContent.trim() || "",
      discount: discount?.textContent.trim() || "",
      meta: meta?.textContent.trim() || "",
      originalCard: card
    });

  });

  // Render products
  const renderProducts = (items) => {

    productGrid.innerHTML = "";

    noResults.classList.toggle("active", items.length === 0);
    productGrid.style.display = items.length ? "grid" : "none";

    resultsCount.textContent = items.length
      ? `${items.length} fragrances`
      : "";

    items.slice(0, 8).forEach((product, index) => {

      const item = document.createElement("div");

      item.className = "search-product-card";
      item.style.animationDelay = `${index * 50}ms`;
      item.tabIndex = 0;
      item.setAttribute("role", "button");
      item.setAttribute("aria-label", `View ${product.title}`);

      const image = document.createElement("img");
      image.src = product.image;
      image.alt = product.title;
      image.loading = "lazy";

      const imageWrap = document.createElement("div");
      imageWrap.className = "search-product-image";
      imageWrap.appendChild(image);

      const info = document.createElement("div");
      info.className = "search-product-info";

      const title = document.createElement("h4");
      title.textContent = product.title;

      const priceWrap = document.createElement("div");
      priceWrap.className = "search-product-price";

      const currentPrice = document.createElement("strong");
      currentPrice.textContent = product.price;

      priceWrap.appendChild(currentPrice);

      if (product.oldPrice) {
        const oldPrice = document.createElement("del");
        oldPrice.textContent = product.oldPrice;
        priceWrap.appendChild(oldPrice);
      }

      if (product.discount) {
        const discount = document.createElement("span");
        discount.textContent = product.discount;
        priceWrap.appendChild(discount);
      }

      info.append(title, priceWrap);
      item.append(imageWrap, info);

      const viewProduct = () => {
        closeSearch();

        setTimeout(() => {
          product.originalCard.scrollIntoView({
            behavior: "smooth",
            block: "center"
          });
        }, 350);
      };

      item.addEventListener("click", viewProduct);

      item.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          viewProduct();
        }
      });

      productGrid.appendChild(item);

    });

    if (window.lucide) lucide.createIcons();
  };

  // Search products
  const filterProducts = (value) => {

    const query = value.trim().toLowerCase();

    const filtered = products.filter(product => {

      const searchableText =
        `${product.title} ${product.meta}`.toLowerCase();

      return searchableText.includes(query);
    });

    resultsHeading.textContent = query
      ? "Search Results"
      : "Recommended For You";

    trendingSection.style.display = query ? "none" : "block";

    renderProducts(query ? filtered : products);
  };

  // Open Search
  const openSearch = () => {

    searchPanel.classList.add("active");
    searchToggle.classList.add("active");

    searchToggle.setAttribute("aria-expanded", "true");

    filterProducts(searchInput.value);

    setTimeout(() => searchInput.focus(), 250);
  };

  // Close Search
  const closeSearch = () => {

    searchPanel.classList.remove("active");
    searchToggle.classList.remove("active");

    searchToggle.setAttribute("aria-expanded", "false");
  };

  searchToggle.addEventListener("click", event => {
    event.preventDefault();
    event.stopPropagation();

    searchPanel.classList.contains("active")
      ? closeSearch()
      : openSearch();
  });

  searchClose.addEventListener("click", closeSearch);

  // Live search
  searchInput.addEventListener("input", () => {

    searchClear.classList.toggle(
      "visible",
      searchInput.value.length > 0
    );

    filterProducts(searchInput.value);

  });

  // Clear search
  searchClear.addEventListener("click", () => {

    searchInput.value = "";
    searchClear.classList.remove("visible");

    filterProducts("");

    searchInput.focus();

  });

  // Trending searches
  document.querySelectorAll(".trending-tag").forEach(tag => {

    tag.addEventListener("click", () => {

      document.querySelectorAll(".trending-tag")
        .forEach(item => item.classList.remove("active"));

      tag.classList.add("active");

      searchInput.value = tag.dataset.search;
      searchClear.classList.add("visible");

      filterProducts(searchInput.value);

    });

  });

  // Close outside
  document.addEventListener("click", event => {

    if (
      !searchPanel.contains(event.target) &&
      !searchToggle.contains(event.target)
    ) {
      closeSearch();
    }

  });

  // Escape key
  document.addEventListener("keydown", event => {

    if (event.key === "Escape") {
      closeSearch();
    }

  });

  // Initial recommended products
  filterProducts("");

})();



/* =========================================
   05. HERO IMAGE SLIDER
========================================= */

(() => {
  const hero = document.querySelector(".hero-banner");

  if (!hero || typeof Swiper === "undefined") return;

  const heroSlides = hero.querySelectorAll(".swiper-slide").length;
  const canLoopHero = heroSlides > 1;

  new Swiper(".heroSwiper", {

    loop: canLoopHero,

    effect: "fade",

    fadeEffect: {
      crossFade: true
    },

    autoHeight: true,

    speed: 1200,

    watchOverflow: true,

    autoplay: canLoopHero ? {
      delay: 5000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    } : false,

    pagination: {
      el: ".hero-pagination",
      clickable: true
    },

    on: {
      init: function () {

        const images = hero.querySelectorAll(".hero-slide img");

        Promise.all(
          Array.from(images).map(img => {

            if (img.complete) {
              return Promise.resolve();
            }

            return new Promise(resolve => {
              img.addEventListener("load", resolve, {
                once: true
              });

              img.addEventListener("error", resolve, {
                once: true
              });
            });

          })
        ).then(() => {
          hero.classList.add("is-loaded");
        });

      }
    }

  });
})();


/* =========================================
   06. CATEGORY SLIDER - SWIPER
========================================= */

(() => {

  const categoryElement = document.querySelector(".categorySwiper");

  if (!categoryElement || typeof Swiper === "undefined") return;

  const catSlides = categoryElement.querySelectorAll(".swiper-slide").length;
  const canLoopCat = catSlides > 4;

  new Swiper(categoryElement, {

    loop: canLoopCat,

    speed: 800,

    spaceBetween: 20,

    slidesPerView: 4,

    grabCursor: true,

    watchOverflow: true,

    autoplay: canLoopCat ? {
      delay: 3500,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    } : false,

    navigation: {
      nextEl: ".category-next",
      prevEl: ".category-prev"
    },

    pagination: {
      el: ".category-pagination",
      clickable: true
    },

    breakpoints: {

      0: {
        slidesPerView: 1.5,
        spaceBetween: 12
      },

      480: {
        slidesPerView: 2,
        spaceBetween: 15
      },

      768: {
        slidesPerView: 3,
        spaceBetween: 18
      },

      1024: {
        slidesPerView: 4,
        spaceBetween: 20
      },

      1400: {
        slidesPerView: 4,
        spaceBetween: 25
      }

    }

  });

})();


/* =========================================
   07. BESTSELLER PRODUCT SLIDER
========================================= */

(() => {

  const bestsellerElement = document.querySelector(".bestsellerSwiper");

  if (!bestsellerElement || typeof Swiper === "undefined") return;

  const bestSlides = bestsellerElement.querySelectorAll(".swiper-slide").length;
  const canLoopBest = bestSlides > 4;

  new Swiper(bestsellerElement, {

    loop: canLoopBest,

    speed: 800,

    spaceBetween: 20,

    slidesPerView: 4,

    grabCursor: true,

    watchOverflow: true,

    autoplay: canLoopBest ? {
      delay: 3500,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    } : false,

    navigation: {
      nextEl: ".bestseller-next",
      prevEl: ".bestseller-prev"
    },

    pagination: {
      el: ".bestseller-pagination",
      clickable: true
    },

    breakpoints: {

      0: {
        slidesPerView: 1.3,
        spaceBetween: 12
      },

      480: {
        slidesPerView: 2,
        spaceBetween: 15
      },

      768: {
        slidesPerView: 3,
        spaceBetween: 18
      },

      1024: {
        slidesPerView: 3,
        spaceBetween: 20
      },

      1280: {
        slidesPerView: 4,
        spaceBetween: 20
      }

    }

  });

})();


/* =========================================
   08. WISHLIST BUTTON (Handled globally via AJAX)
========================================= */
// Wishlist toggle handled globally in front.blade.php with CSRF & session sync
// New Arrival

/* =========================================
   09. NEW ARRIVALS SWIPER
========================================= */

(() => {

  const newArrivalsElement = document.querySelector(".newArrivalsSwiper");

  if (!newArrivalsElement || typeof Swiper === "undefined") return;

  const naSlides = newArrivalsElement.querySelectorAll(".swiper-slide").length;
  const canLoopNa = naSlides > 4;

  new Swiper(newArrivalsElement, {

    loop: canLoopNa,

    speed: 800,

    spaceBetween: 20,

    slidesPerView: 4,

    grabCursor: true,

    watchOverflow: true,

    autoplay: canLoopNa ? {
      delay: 3500,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    } : false,

    navigation: {
      nextEl: ".new-arrivals-next",
      prevEl: ".new-arrivals-prev"
    },

    pagination: {
      el: ".new-arrivals-pagination",
      clickable: true,
      dynamicBullets: false
    },

    breakpoints: {

      0: {
        slidesPerView: 1.3,
        spaceBetween: 12
      },

      480: {
        slidesPerView: 2,
        spaceBetween: 15
      },

      768: {
        slidesPerView: 3,
        spaceBetween: 18
      },

      1024: {
        slidesPerView: 3,
        spaceBetween: 20
      },

      1280: {
        slidesPerView: 4,
        spaceBetween: 20
      }

    }

  });

})();

// Listing

document.addEventListener("DOMContentLoaded", function () {

  const shopPage = document.querySelector(".shop-page");

  if (!shopPage) return;

  const filterToggle = shopPage.querySelector(".filter-toggle");
  const closeFilters = shopPage.querySelector(".close-filters");
  const applyFilters = shopPage.querySelector(".apply-filters");
  const overlay = shopPage.querySelector(".shop-overlay");
  const clearFilters = shopPage.querySelector(".clear-filters");

  // Open filters
  function openFilters() {
    shopPage.classList.add("filters-open");
    document.body.style.overflow = "hidden";
  }

  // Close filters
  function closeFilterDrawer() {
    shopPage.classList.remove("filters-open");
    document.body.style.overflow = "";
  }

  if (filterToggle) {
    filterToggle.addEventListener("click", openFilters);
  }

  if (closeFilters) {
    closeFilters.addEventListener("click", closeFilterDrawer);
  }

  if (applyFilters) {
    applyFilters.addEventListener("click", closeFilterDrawer);
  }

  if (overlay) {
    overlay.addEventListener("click", closeFilterDrawer);
  }

  // Clear filters
  if (clearFilters) {
    clearFilters.addEventListener("click", function () {
      shopPage.querySelectorAll('.filter-options input[type="checkbox"]')
        .forEach(function (input) {
          input.checked = false;
        });
    });
  }

  // Filter accordion (accordion behavior: opening selected closes others)
  const filterTitles = shopPage.querySelectorAll(".filter-group-title");

  function setFilterGroupState(button, open) {
    const options = button.nextElementSibling;
    button.setAttribute("aria-expanded", String(open));
    if (options) {
      options.style.display = open ? "block" : "none";
    }
    const icon = button.querySelector("svg, i");
    if (icon) {
      const newIcon = document.createElement("i");
      newIcon.setAttribute("data-lucide", open ? "minus" : "plus");
      icon.replaceWith(newIcon);
    }
  }

  filterTitles.forEach(function (button) {
    button.addEventListener("click", function () {
      const isExpanded = this.getAttribute("aria-expanded") === "true";

      if (isExpanded) {
        // Toggle closed
        setFilterGroupState(this, false);
      } else {
        // Close all other filter groups
        filterTitles.forEach(function (otherBtn) {
          if (otherBtn !== button) {
            setFilterGroupState(otherBtn, false);
          }
        });
        // Open the selected filter group
        setFilterGroupState(this, true);
      }

      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }
    });
  });

  // On page load, ensure group with active/checked filters is open and others closed
  const groupsWithSelection = [];
  shopPage.querySelectorAll(".filter-group").forEach(function (group) {
    const checked = group.querySelectorAll('input[type="checkbox"]:checked');
    let hasRealSelection = false;
    checked.forEach(function (chk) {
      if (chk.name !== "col_all") {
        hasRealSelection = true;
      }
    });
    if (hasRealSelection) {
      groupsWithSelection.push(group);
    }
  });

  if (groupsWithSelection.length > 0) {
    shopPage.querySelectorAll(".filter-group").forEach(function (group) {
      const btn = group.querySelector(".filter-group-title");
      if (btn) {
        const shouldBeOpen = groupsWithSelection.includes(group);
        setFilterGroupState(btn, shouldBeOpen);
      }
    });
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  }

  // Wishlist toggle handled globally via AJAX in front.blade.php (with CSRF & session sync)

  // Lucide icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }

});


// Detaisl

document.addEventListener("DOMContentLoaded", function () {

  /* =====================================
     PRODUCT IMAGE GALLERY
  ===================================== */

  const mainImage = document.getElementById("mainProductImage");
  const thumbnails = document.querySelectorAll(".product-thumbnails .thumbnail");

  thumbnails.forEach(function (thumbnail) {

    thumbnail.addEventListener("click", function () {

      const imageUrl = this.dataset.image;

      if (mainImage && imageUrl) {

        mainImage.style.opacity = "0";

        setTimeout(function () {
          mainImage.src = imageUrl;
          mainImage.style.opacity = "1";
        }, 180);

      }

      thumbnails.forEach(function (item) {
        item.classList.remove("active");
      });

      this.classList.add("active");

    });

  });


  /* =====================================
     PRODUCT ML / SIZE SELECTION
  ===================================== */

  const mlOptions = document.querySelectorAll(".ml-option");
  const selectedML = document.getElementById("selectedProductML");
  const selectedSizeLabel = document.querySelector(".selected-size-label");
  const currentPrice = document.querySelector(".current-price");
  const oldPrice = document.querySelector(".old-price");
  const discountLabel = document.querySelector(".discount-label");

  const productPrices = {
    "5.5 ML": {
      price: "₹299",
      oldPrice: "₹399",
      discount: "25% OFF"
    },
    "12 ML": {
      price: "₹499",
      oldPrice: "₹699",
      discount: "29% OFF"
    },
    "50 ML": {
      price: "₹1,499",
      oldPrice: "₹1,999",
      discount: "25% OFF"
    },
    "100 ML": {
      price: "₹2,499",
      oldPrice: "₹3,299",
      discount: "24% OFF"
    }
  };

  mlOptions.forEach(function (button) {

    button.addEventListener("click", function () {

      const selectedSize = this.dataset.ml;

      mlOptions.forEach(function (item) {
        item.classList.remove("active");
      });

      this.classList.add("active");

      if (selectedML) {
        selectedML.value = selectedSize;
      }

      if (selectedSizeLabel) {
        selectedSizeLabel.textContent = selectedSize;
      }

      const dynamicPrice = this.dataset.price;
      const dynamicOldPrice = this.dataset.oldPrice;
      const dynamicDiscount = this.dataset.discount;
      const selectedPrice = productPrices[selectedSize];

      if (dynamicPrice && currentPrice) {
        currentPrice.textContent = dynamicPrice;
      } else if (selectedPrice && currentPrice) {
        currentPrice.textContent = selectedPrice.price;
      }

      if (dynamicOldPrice && oldPrice) {
        oldPrice.textContent = dynamicOldPrice;
      } else if (selectedPrice && oldPrice) {
        oldPrice.textContent = selectedPrice.oldPrice;
      }

      if (dynamicDiscount && discountLabel) {
        discountLabel.textContent = dynamicDiscount;
      } else if (selectedPrice && discountLabel) {
        discountLabel.textContent = selectedPrice.discount;
      }

    });

  });


  /* =====================================
     QUANTITY SELECTOR
  ===================================== */

  const quantityInput = document.getElementById("productQuantity");
  const quantityMinus = document.getElementById("quantityMinus");
  const quantityPlus = document.getElementById("quantityPlus");

  // Prevent double event binding if page provides its own purchase row controller (e.g. product_details)
  if (!document.querySelector(".product-purchase-row")) {
    if (quantityMinus && quantityInput) {
      quantityMinus.addEventListener("click", function () {
        let quantity = parseInt(quantityInput.value, 10) || 1;
        if (quantity > 1) {
          quantityInput.value = quantity - 1;
        }
      });
    }

    if (quantityPlus && quantityInput) {
      quantityPlus.addEventListener("click", function () {
        let quantity = parseInt(quantityInput.value, 10) || 1;
        const max = parseInt(quantityInput.getAttribute("max"), 10) || 99;
        if (quantity < max) {
          quantityInput.value = quantity + 1;
        }
      });
    }

    if (quantityInput) {
      quantityInput.addEventListener("change", function () {
        let quantity = parseInt(this.value, 10);
        if (isNaN(quantity) || quantity < 1) {
          this.value = 1;
        }
      });
    }
  }


  /* =====================================
     DESCRIPTION TABS
  ===================================== */

  const descriptionTabs = document.querySelectorAll(".description-tab");
  const descriptionContents = document.querySelectorAll(".description-content");

  descriptionTabs.forEach(function (tab) {

    tab.addEventListener("click", function () {

      const target = this.dataset.tab;

      descriptionTabs.forEach(function (item) {
        item.classList.remove("active");
      });

      descriptionContents.forEach(function (content) {
        content.classList.remove("active");
      });

      this.classList.add("active");

      const targetContent = document.getElementById(target);

      if (targetContent) {
        targetContent.classList.add("active");
      }

    });

  });


  /* =====================================
     COMBO OFFERS SWIPER
  ===================================== */

  const comboSection = document.querySelector(".combo-offers-section");

  if (comboSection && typeof Swiper !== "undefined") {

    const comboSwiperElement = comboSection.querySelector(".newArrivalsSwiper");

    if (comboSwiperElement) {

      new Swiper(comboSwiperElement, {

        slidesPerView: 4,
        spaceBetween: 20,
        speed: 750,
        loop: false,
        grabCursor: true,
        watchOverflow: true,

        navigation: {
          nextEl: comboSection.querySelector(".new-arrivals-next"),
          prevEl: comboSection.querySelector(".new-arrivals-prev")
        },

        pagination: {
          el: comboSection.querySelector(".new-arrivals-pagination"),
          clickable: true
        },

        breakpoints: {
          320: {
            slidesPerView: 1.2,
            spaceBetween: 12
          },

          480: {
            slidesPerView: 2,
            spaceBetween: 15
          },

          768: {
            slidesPerView: 3,
            spaceBetween: 18
          },

          1024: {
            slidesPerView: 4,
            spaceBetween: 20
          }
        }

      });

    }

  }


  /* =====================================
     WISHLIST BUTTON (Handled globally via AJAX)
  ===================================== */


  /* =====================================
     LUCIDE ICONS
  ===================================== */

  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }

});
// Contact-us

document.addEventListener("DOMContentLoaded", function () {

  // Lucide icons
  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }

  // Smooth scroll reveal
  const revealElements = document.querySelectorAll(
    ".contact-information, .contact-info-item, .contact-form-wrapper, .contact-map-heading, .contact-map, .contact-faq-item"
  );

  if ("IntersectionObserver" in window) {

    const observer = new IntersectionObserver(function (entries) {

      entries.forEach(function (entry) {

        if (entry.isIntersecting) {
          entry.target.classList.add("contact-visible");
          observer.unobserve(entry.target);
        }

      });

    }, {
      threshold: 0.12,
      rootMargin: "0px 0px -35px 0px"
    });

    revealElements.forEach(function (element, index) {

      element.classList.add("contact-reveal");

      element.style.transitionDelay =
        Math.min(index % 4, 3) * 100 + "ms";

      observer.observe(element);

    });

  }

  // FAQ: keep one item open at a time
  const faqItems = document.querySelectorAll(".contact-faq-item");

  faqItems.forEach(function (item) {

    item.addEventListener("toggle", function () {

      if (this.open) {

        faqItems.forEach(function (otherItem) {
          if (otherItem !== item) {
            otherItem.open = false;
          }
        });

      }

    });

  });

});

