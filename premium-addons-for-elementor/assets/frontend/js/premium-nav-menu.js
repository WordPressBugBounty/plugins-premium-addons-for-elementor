(function ($) {

	/**Premium Nav Menu */
	var PremiumNavMenuHandler = function ($scope, $) {
		/**
		 * we don't need to wait for content dom load since the script is loaded in the footer.
		 * we reset the visiblity/opacity in both editor & frontend as elementor templates are treated as frontend pages when rendered in the editor ( of other pages).
		 */
		$scope.find('.premium-nav-widget-container').css({ visibility: 'inherit', opacity: 'inherit' });

		var settings = $scope.find('.premium-nav-widget-container').data('settings');

		if (!settings) {
			return;
		}

		var $menuContainer = $scope.find('.premium-mobile-menu'),
			$menuToggler = $scope.find('.premium-hamburger-toggle'),
			$hamMenuCloser = $scope.find('.premium-mobile-menu-close'),
			$centeredItems = $scope.find('.premium-mega-content-centered'),
			$fullWidthItems = $scope.find('.premium-nav-menu-container li[data-full-width="true"]'),
			stickyProps = {},
			refreshPos = false,
			stickyIndex = 'stickyPos' + $scope.data('id'),
			stickyWidthIndex = 'stickyWidth' + $scope.data('id'),
			disablePageScroll = $scope.hasClass('premium-disable-scroll-yes') ? true : false,
			delay = getComputedStyle($scope[0]).getPropertyValue('--pa-mega-menu-delay') || 300,
			renderMobileMenu = settings.renderMobileMenu,
			isEditMode = elementorFrontend.isEditMode(),
			hoverTimeout;

		//Get Element On Page Option
		$scope.find('div[data-mega-content]').each(function (index, elem) {
			var $currentItem = $(elem),
				targetElement = $currentItem.data('mega-content');

			if ($(targetElement).length > 0) {

				var $targetElement = $(targetElement);

				$targetElement.attr('data-menu-id', $scope.data('id'));

				$currentItem.append($targetElement.clone(true).addClass('pa-cloned-element'));

			}

		});

		if (!isEditMode) {
			//Remove Element On Page Option If on Frontend
			$('div[data-menu-id="' + $scope.data('id') + '"]').not('.pa-cloned-element').remove();

			// handle anchor links with IDs => EX : https::somelink.com/home#section.
			var anchorLinks = $scope.find('.premium-item-anchor');

			if (anchorLinks.length) {
				handleAnchorLinks(anchorLinks);
			}
		}

		/**
		 * Save current device to use it later to determine if the device changed on resize.
		 */
		window.PaCurrStickyDevice = elementorFrontend.getCurrentDeviceMode();

		// make sure it's removed when the option is disabled.
		if (isEditMode && !disablePageScroll) {
			$('body').removeClass('premium-scroll-disabled');
		}

		// centered elements.
		$centeredItems.each(function (index, item) {
			$(item).closest(".premium-nav-menu-item").addClass("premium-mega-item-static");
		});

		if ('slide' === settings.mobileLayout || 'slide' === settings.mainLayout) {
			$scope.addClass('premium-ver-hamburger-menu');
		}

		if (settings.rn_badges) {
			addRandBadges(settings.rn_badges);
		}

		// check badges dot/grow effect.
		if ('dot' === settings.hoverEffect) {

			var $badgedItems = $scope.find('.premium-mega-content-container .premium-badge-dot, .premium-sub-menu .premium-badge-dot');

			$badgedItems.each(function (index, $item) {
				$($item).mouseenter(function () {
					$($item).removeClass('premium-badge-dot');
				}).mouseleave(function () {
					$($item).addClass('premium-badge-dot');
				});
			});
		}

		// close mobile menu after clicking.
		if (settings.closeAfterClick) {
			$menuContainer.find('.premium-menu-link').on('click.paAfterClick', function () {
				// check if it has children
				var hasChildern = itemHasChildren(this);

				if (!hasChildern) {
					// close mobile menu
					if ('slide' === settings.mainLayout || 'slide' === settings.mobileLayout) {
						// if ($scope.hasClass('premium-nav-slide')) {
						$hamMenuCloser.click();
					} else {
						$menuToggler.click();
					}
				}
			});
		}

		if (renderMobileMenu) {
			var isMobileMenu = null,
				isDesktopMenu = null;

			checkBreakPoint(settings);
		}

		// not using nested condition => more readable this way.
		// if (isEditMode && !renderMobileMenu) {
		if (!renderMobileMenu) { // those classes are used in both editor & frontend.
			$scope.find('.premium-nav-default').removeClass('premium-nav-default');
			$scope.removeClass('premium-hamburger-menu');
		}

		if ($scope.hasClass('premium-nav-hor')) {
			// $(window).resize();
			checkMegaContentWidth();
		}

		checkStickyEffect();

		//Should dropdown be triggered on whole item or icon click.
		var triggerSelector = 'item' === settings.submenuTrigger ? ' > .premium-menu-link' : ' > .premium-menu-link > .premium-dropdown-icon';

		if (['hor', 'ver'].includes(settings.mainLayout)) {

			if ('hover' === settings.submenuEvent) {

				$scope.find('.premium-nav-menu-item').on('mouseenter.PaItemHover', function (e) {

					e.stopPropagation();

					clearTimeout(hoverTimeout);

					$(this).siblings().removeClass('premium-item-hovered'); // unset hovered items only for this menu.

					$(this).addClass('premium-item-hovered');

					if ($(this).hasClass('premium-sub-menu-item'))
						$(this).parents('.premium-nav-menu-item').addClass('premium-item-hovered');

				});

				$scope.on('mouseleave.PaItemHover', function (e) {

					hoverTimeout = setTimeout(function () {
						$scope.find('.premium-item-hovered').removeClass('premium-item-hovered');
					}, delay);
				});

				// we need to make sure that premium-item-hover is not removed when hovering over a sub/mega menu.
				$scope.find('.premium-sub-menu, .premium-mega-content-container').on('mouseenter.PaItemHover', function (e) {

					e.stopPropagation();

					var $menuItem = $(this).parents('.premium-nav-menu-item').first();

					clearTimeout(hoverTimeout);

					$menuItem.siblings().removeClass('premium-item-hovered'); // remove it from the menu item in the same widget only

					$menuItem.addClass('premium-item-hovered');
				}).on('mouseleave.PaItemHover', function (e) {

					clearTimeout(hoverTimeout);
					$(this).parents('.premium-nav-menu-item').first().removeClass('premium-item-hovered');
				});
			} else { // click

				var $trigger = $scope.find('.premium-nav-menu-container .premium-nav-menu-item.menu-item-has-children' + triggerSelector);

				/**
				 * To prevent events overlapping if the user switched between hover/click
				 * while building the menu.
				 */
				if (isEditMode) {
					$scope.off('mouseleave.PaItemHover');
				}

				$trigger.off('click.PaItemClick'); // to prevent duplications.
				$trigger.on('click.PaItemClick', function (e) {

					e.preventDefault();
					e.stopPropagation();

					var $menuItem = $(this).parents('.premium-nav-menu-item').first();

					// remove it from the menu item in the same widget only
					$menuItem.siblings().removeClass('premium-item-hovered').find('.premium-item-hovered').removeClass('premium-item-hovered');

					$menuItem.toggleClass('premium-item-hovered');

				});
			}
		}

		$hamMenuCloser.on('click', function () {
			$scope.find('.premium-mobile-menu-outer-container, .premium-nav-slide-overlay').removeClass('premium-vertical-toggle-open');
			$('body').removeClass('premium-scroll-disabled');
		});

		var canBeClicked = true;
		$menuToggler.on('click', function () {

			if ('slide' === settings.mobileLayout || 'slide' === settings.mainLayout) {
				$scope.find('.premium-mobile-menu-outer-container, .premium-nav-slide-overlay').addClass('premium-vertical-toggle-open');

				if (disablePageScroll) {
					$('body').addClass('premium-scroll-disabled');
				}

				$menuToggler.toggleClass('premium-toggle-opened'); // show/hide close icon/text.
			} else {

				if (canBeClicked) {

					canBeClicked = false;
					if ($($menuContainer).hasClass('premium-active-menu')) {

						$scope.find('.premium-mobile-menu-container').slideUp(150, function () {

							$menuContainer.removeClass('premium-active-menu');
							$scope.find('.premium-mobile-menu-container').show();

							setTimeout(function () {
								canBeClicked = true;
								$menuToggler.removeClass('premium-toggle-opened'); // hide close icon/text.
							}, 100);


						});
					} else {

						$menuContainer.addClass('premium-active-menu');

						$menuToggler.addClass('premium-toggle-opened'); // show close icon/text.

						canBeClicked = true;

					}
				}
			}

			// $menuToggler.toggleClass('premium-toggle-opened'); // show/hide close icon/text.

		});

		// Modify the mobile menu click handler.
		$menuContainer.find(".premium-nav-menu-item " + triggerSelector).on('click', function (e) {
			var $this = $(this),
				$parent = $this.closest(".premium-nav-menu-item"),
				hasSubmenu = itemHasChildren(this);

			// For 'item' trigger: Only prevent default if submenu exists.
			if ('item' === settings.submenuTrigger) {
				if (!hasSubmenu) return; // Allow link navigation.
			}

			e.stopPropagation();
			e.preventDefault();

			//If it was opened, then close it.
			if ($parent.hasClass('premium-active-menu')) {
				$parent.toggleClass('premium-active-menu');

			} else {
				//Close any other opened items.
				$menuContainer.find('.premium-active-menu').toggleClass('premium-active-menu');
				//Then, open this item.
				$parent.toggleClass('premium-active-menu');
				// make sure the parent node is always open whenever the child node is opened.
				$($parent).parents('.premium-nav-menu-item.menu-item-has-children').toggleClass('premium-active-menu');
			}
		});

		$(document).on('click', '.premium-nav-slide-overlay', function () {
			$scope.find('.premium-mobile-menu-outer-container, .premium-nav-slide-overlay').removeClass('premium-vertical-toggle-open');
			$('body').removeClass('premium-scroll-disabled');
		});


		$(document).on('click.PaCloseMegaMenu', function (event) {

			var isTabsItem = $(event.target).closest('.premium-tabs-nav-list-item').length,
				isWidgetContainer = $(event.target).closest('.premium-nav-widget-container').length;

			if (!isWidgetContainer && !isTabsItem) {
				if ($($menuContainer).hasClass('premium-active-menu')) {
					$menuToggler.click();
				}

				if ('click' === settings.submenuEvent) {
					$scope.find('.premium-nav-menu-container .premium-item-hovered').removeClass('premium-item-hovered');
				}
			}

		});

		$(window).on('resize', function () {

			if (window.PaCurrStickyDevice !== elementorFrontend.getCurrentDeviceMode()) {
				refreshPos = true;
				window.PaCurrStickyDevice = elementorFrontend.getCurrentDeviceMode();
			}

			if (renderMobileMenu) {
				checkBreakPoint(settings);
			}

			if (isEditMode && !renderMobileMenu) {
				$scope.removeClass('premium-hamburger-menu');
			}

			if ($scope.hasClass('premium-nav-hor')) {
				checkMegaContentWidth();
			}

			checkStickyEffect();
		});

		// vertical toggler.
		if ($scope.hasClass('premium-ver-toggle-yes') && $scope.hasClass('premium-ver-click')) {
			$scope.find('.premium-ver-toggler').on('click', function () {
				$scope.find('.premium-nav-widget-container').toggleClass('premium-ver-collapsed', 500);
			});
		}

		//************Helper Functions************ */

		/**
		 * Adds the 'premium-active-item' to anchor links that has the '#'.
		 * @param {object} $anchorLinks
		 */
		function handleAnchorLinks($anchorLinks) {
			var sections = [];

			// Map links to sections
			$anchorLinks.each(function (index, anchorLink) {
				var $hashLink = $(anchorLink).find('> .premium-menu-link'),
					isValidId = /^#[A-Za-z][\w-]*$/,    // IDs should start with '#' and contain only [characters,numbers, -, _ ]
					targetId = $hashLink[0] ? $hashLink[0].hash : null;

				if (targetId && targetId.length && isValidId.test(targetId)) {

					try {
						var $section = $(targetId);

						if ($section.length) {
							sections.push({ link: anchorLink, section: $section[0] });
						}

					} catch (error) {
						console.log(error)
					}
				}
			});

			if (sections.length === 0) return;

			var observer = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {

							// Find the active link for the visible section.
							var activeLink = sections.find(function (s) {
								return s.section === entry.target;
							}).link;

							addActiveItemClass(activeLink);
						}
					});
				},
				{
					threshold: 0.5,
				}
			);

			// Observe each section
			sections.forEach(function (sec) {
				observer.observe(sec.section);
			});
		}

		function addActiveItemClass(item) {

			// make sure the class is added to both the desktop and mobile menu item
			var currentHash = $(item).find('> .premium-menu-link').attr('href');

			$scope.find('.premium-active-item').each(function () {
				var $link = $(this).find('> .premium-menu-link');

				if ($link.attr('href') !== currentHash) {
					$(this).removeClass('premium-active-item'); // Remove the class from the parent element
				}
			});

			$(item).addClass('premium-active-item')
		}

		// Set menu items to full width.
		function checkMegaContentWidth() {
			$fullWidthItems.each(function (index, item) {
				fullWidthContent($(item));
			});
		}

		/**
		 * Full Width Mega Content.
		 */
		function fullWidthContent($item) {
			var customSelector = $($item).data('full-width-selector');

			if (customSelector) {
				var $parentSec = $(customSelector);
			} else {
				var isContainer = elementorFrontend.config.experimentalFeatures.container,
					$parentSec = $scope.parents('.e-con').last();

				$parentSec = !isContainer || $parentSec.length < 1 ? $scope.closest('.elementor-top-section') : $parentSec;
			}

			var width = $parentSec.outerWidth(),
				sectionLeft = $parentSec.offset().left - $item.offset().left;

			$($item).removeClass('premium-mega-item-static').find('.premium-mega-content-container, > .premium-sub-menu').css({
				width: width + 'px',
				left: sectionLeft + 'px',
			});
		}

		function checkBreakPoint(settings) {

			//Trigger small screen menu.
			if (settings.breakpoint >= $(window).outerWidth() && !isMobileMenu) {
				// remove the vertical toggler.
				$scope.find('.premium-ver-toggler').css('display', 'none');
				$scope.addClass('premium-hamburger-menu');
				$scope.find('.premium-active-menu').removeClass('premium-active-menu');
				stretchDropdown($scope.find('.premium-stretch-dropdown .premium-mobile-menu-container'));

				isMobileMenu = true;
				isDesktopMenu = false;

				//Trigger large screen menu.
			} else if (settings.breakpoint < $(window).outerWidth() && !isDesktopMenu) {
				// show the vertical toggler if enabled.
				if ($scope.hasClass('premium-ver-toggle-yes')) {
					$scope.find('.premium-ver-toggler').css('display', 'flex');
				}

				$menuToggler.removeClass('premium-toggle-opened');
				$scope.find(".premium-mobile-menu-container .premium-active-menu").removeClass("premium-active-menu");
				$scope.removeClass('premium-hamburger-menu premium-ham-dropdown');
				$scope.find('.premium-vertical-toggle-open').removeClass('premium-vertical-toggle-open');
				$scope.find('.premium-nav-default').removeClass('premium-nav-default');

				isDesktopMenu = true;
				isMobileMenu = false;
			}

		}

		/**
		 * Full Width Option.
		 * Shows the mobile menu beneath the widget's parent(section).
		 */
		function stretchDropdown($menu) {

			if (!$menu.length) return;

			var isContainer = elementorFrontend.config.experimentalFeatures.container,
				$parentSec = $scope.parents('.e-con').last();

			$parentSec = !isContainer || $parentSec.length < 1 ? $scope.closest('.elementor-top-section') : $parentSec;

			var width = $($parentSec).outerWidth(),
				widgetTop = $scope.offset().top,
				parentBottom = $($parentSec).offset().top + $($parentSec).outerHeight(),
				stretchTop = parentBottom - widgetTop,
				stretchLeft = $scope.offset().left - $($parentSec).offset().left;

			$($menu).css({
				width: width + 'px',
				left: '-' + stretchLeft + 'px',
				top: stretchTop + 'px',
			});
		}

		/**
		 * Sticky Effect.
		 */

		function checkStickyEffect() {

			var isSticky = $scope.hasClass('premium-nav-sticky-yes') &&
				// settings.stickyOptions &&
				$('#' + settings.stickyOptions.targetId).length &&
				!settings.stickyOptions.disableOn.includes(elementorFrontend.getCurrentDeviceMode());

			if (isSticky) {
				stickyProps = settings.stickyOptions;

				stickyProps.spacerClass = 'premium-sticky-spacer-' + $('#' + stickyProps.targetId).data('id');

				$('#' + stickyProps.targetId).addClass('premium-sticky-active');

				setStickyWidth(stickyProps);

				// Add spacer to save the sticky target space in the dom.
				if (0 === $('.' + stickyProps.spacerClass).length) {
					$('<div class="' + stickyProps.spacerClass + '"></div>').insertBefore('#' + stickyProps.targetId);
				}

				$(window).on('load', applyStickyEffect);
				$(window).on('scroll.PaStickyNav', applyStickyEffect);

			} else {
				$(window).off('scroll.PaStickyNav');
				$('.' + stickyProps.spacerClass).remove(); // remove spacer
				$('#' + stickyProps.targetId).removeClass('premium-sticky-parent premium-sticky-active premium-sticky-parent-' + $scope.data('id')).css({ // unset style
					top: 'unset',
					width: 'inherit',
					position: 'relative'
				});
			}
		}

		/**
		 * we need to get the original width before setting
		 * the position to fixed.
		 */
		function setStickyWidth(stickyProps) {
			// TODO: check if we can use the spacer's width directly instead.
			var currStickyWidth = stickyWidthIndex + elementorFrontend.getCurrentDeviceMode(),
				isSticky = $('#' + stickyProps.targetId).hasClass('premium-sticky-parent'); // ==> fixed position

			if (isSticky) {
				$('#' + stickyProps.targetId).css({
					position: 'relative',
					width: 'inherit'
				});
			}

			window[currStickyWidth] = $('#' + stickyProps.targetId).outerWidth() + 'px';

			if (isSticky) {

				$('#' + stickyProps.targetId).css({
					position: 'fixed',
					width: window[currStickyWidth]
				});
			}
		}

		function applyStickyEffect() {

			var $adminBarHeight = elementorFrontend.elements.$wpAdminBar.height() ? elementorFrontend.elements.$wpAdminBar.height() : 0,
				scrollTop = $(window).scrollTop() + $adminBarHeight,
				currStickyWidth = stickyWidthIndex + elementorFrontend.getCurrentDeviceMode();

			if (!window[stickyIndex] || refreshPos) { // save the offset
				window[stickyIndex] = $('.' + stickyProps.spacerClass).offset().top;
				refreshPos = false;
			}

			if (scrollTop >= window[stickyIndex]) {

				$('.' + stickyProps.spacerClass).css('height', $('#' + stickyProps.targetId).outerHeight() + 'px');
				$('#' + stickyProps.targetId).addClass('premium-sticky-parent premium-sticky-parent-' + $scope.data('id')).css({
					width: window[currStickyWidth],
					top: $adminBarHeight,
					position: 'fixed'
				});

			} else {
				$('.' + stickyProps.spacerClass).css('height', '0px');
				$('#' + stickyProps.targetId).removeClass('premium-sticky-parent premium-sticky-parent-' + $scope.data('id')).css({
					top: 'unset',
					width: 'inherit',
					position: 'relative'
				});
			}

			// sticky on scroll option.
			if (stickyProps.onScroll) {
				var $element = document.querySelector('#' + stickyProps.targetId + '.premium-sticky-parent');

				if ($element) {
					$('#' + stickyProps.targetId + '.premium-sticky-parent').addClass('premium-sticky-scroll-yes');
					var headroom = new Headroom($element,
						{
							tolerance: 5,
							classes: {
								initial: "animated",
								pinned: "slideDown",
								unpinned: "slideUp",
								offset: {
									up: $('#' + stickyProps.targetId).outerHeight() + 150, // first time only.
								},
							}
						});

					headroom.init();
				}
			} else {
				$('#' + stickyProps.targetId + '.premium-sticky-parent').removeClass('premium-sticky-scroll-yes');
			}
		}

		/**
		 * Random Badges.
		 */

		function addRandBadges(badges) {

			var $menuContainer = ['hor', 'ver'].includes(settings.mainLayout) ? $scope.find('.premium-nav-menu-container') : $scope.find('.premium-mobile-menu-container');

			badges.forEach(function (badge) {
				var targetCount = $menuContainer.find(badge.selector + ':not(.has-pa-badge)').length;

				if ('' === badge.selector || !targetCount) return;

				// get no of appearnces & elements.
				var randTargetsIndex = getRandTargetsIndex(badge.max, targetCount);

				addBadge(badge, randTargetsIndex);
			});
		}

		function getRandTargetsIndex(max, targetCount) {
			var showTimes = getRandInt(max),
				targetIndex = [];

			for (var index = 0; index < showTimes; index++) {

				var target = getRandInt(targetCount);

				if (!targetIndex.includes(target)) {
					targetIndex.push(target);
				}
			}

			return targetIndex;
		}

		function getRandInt(max) {
			return Math.floor(Math.random() * max) + 1;
		}

		function addBadge(badge, targetsIndex) {

			var badgeHtml = getBadgeHtml(badge),
				targets = $scope.find('.premium-nav-menu-container ' + badge.selector + ':not(.has-pa-badge)'),
				mobileTargets = $scope.find('.premium-mobile-menu-container ' + badge.selector + ':not(.has-pa-badge)'),
				hoverEffectClass = '' !== settings.hoverEffect ? 'premium-badge-' + settings.hoverEffect : '';

			for (var index = 0; index < targetsIndex.length; index++) {

				if (['hor', 'ver'].includes(settings.mainLayout)) {

					$scope.find(targets[targetsIndex[index] - 1])
						.css('position', 'relative')
						.addClass('has-pa-badge ' + hoverEffectClass)
						.append(badgeHtml);
				}

				$scope.find(mobileTargets[targetsIndex[index] - 1])
					.css('position', 'relative')
					.addClass('has-pa-badge ' + hoverEffectClass)
					.append(badgeHtml);

			}
		}

		function getBadgeHtml(badge) {
			return '<span class="premium-rn-badge elementor-repeater-item-' + badge.id + '">' + badge.text + '</span>';
		}

		/**
		 * @param {link} $item .premium-menu-link
		 * @returns boolean
		 */
		function itemHasChildren($item) {
			return $($item).parent('.premium-nav-menu-item').hasClass('menu-item-has-children');
		}
	};

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/premium-nav-menu.default', PremiumNavMenuHandler);
	});

})(jQuery);
