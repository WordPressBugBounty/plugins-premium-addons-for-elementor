(function ($) {

	"use strict";

	var redHadfontLink = document.createElement('link');
	redHadfontLink.rel = 'stylesheet';
	redHadfontLink.href = 'https://fonts.googleapis.com/css?family=Red Hat Display:100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';
	redHadfontLink.type = 'text/css';
	document.head.appendChild(redHadfontLink);

	var poppinsfontLink = document.createElement('link');
	poppinsfontLink.rel = 'stylesheet';
	poppinsfontLink.href = 'https://fonts.googleapis.com/css?family=Poppins:100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';
	poppinsfontLink.type = 'text/css';
	document.head.appendChild(poppinsfontLink);

	var pluaJakartaFontLInk = document.createElement('link');
	pluaJakartaFontLInk.rel = 'stylesheet';
	pluaJakartaFontLInk.href = 'https://fonts.googleapis.com/css?family=Plus Jakarta Sans:100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';
	pluaJakartaFontLInk.type = 'text/css';
	document.head.appendChild(pluaJakartaFontLInk);

	var settings = premiumAddonsSettings.settings;


	window.PremiumAddonsNavigation = function () {

		var self = this,
			$tabs = $(".pa-settings-tab"),
			$elementsTabs = $(".pa-elements-tab"),
			shouldDisableUnused = false;

		var urlString = window.location.href,
			url = new URL(urlString);

		self.init = function () {

			if (!$tabs.length) {
				return;
			}

			self.genButtonDisplay();

			self.initNavTabs($tabs);

			self.initElementsTabs($elementsTabs);

			self.getUnusedWidget();

			self.handleActionField();

			self.handleElementsActions();

			self.handleSearchField();

			self.handleSettingsSave();

			self.handleRollBack();

			self.handleNewsLetterForm();

			self.handlePaproActions();

			self.handleWhiteLabelingAction()

			self.initMcpConfig();

			self.initAiAbilities();

		};

		// AI Abilities tab: accordion and per-category/per-ability persistence.
		self.initAiAbilities = function () {

			var $section = $('#pa-section-ai-abilities');

			if (!$section.length) {
				return;
			}

			var $abilitySwitches = $section.find('.pa-ai-ability-switch input[data-ability]'),
				$categorySwitches = $section.find('.pa-ai-ability-cat-switch input[data-cat]'),
				$allSwitches = $abilitySwitches.add($categorySwitches),
				$status = $section.find('.pa-ai-abilities-status'),
				saveTimer = null,
				requestInFlight = false,
				saveQueued = false,
				lastSavedDisabled = [];

			function collectDisabled() {
				return $abilitySwitches.filter(':not(:checked)').map(function () {
					return $(this).attr('data-ability');
				}).get();
			}

			function updateCategory(category) {
				var sel = '[data-cat="' + category + '"]',
					$members = $abilitySwitches.filter(sel),
					$categorySwitch = $categorySwitches.filter(sel),
					$count = $section.find('.pa-mcp-ability-cat-count' + sel),
					total = $members.length,
					enabledCount = $members.filter(':checked').length;

				$categorySwitch.prop('checked', total === enabledCount)
					.prop('indeterminate', 0 < enabledCount && enabledCount < total);

				$count.find('.pa-count-enabled, .pa-count-enabled-sr').text(enabledCount);
				$count.removeClass('is-all is-some is-none')
					.addClass(total === enabledCount ? 'is-all' : (0 === enabledCount ? 'is-none' : 'is-some'));
			}

			function updateAllCategories() {
				$categorySwitches.each(function () {
					updateCategory($(this).attr('data-cat'));
				});
			}

			function applyDisabled(disabledIds) {
				$abilitySwitches.each(function () {
					$(this).prop('checked', -1 === disabledIds.indexOf($(this).attr('data-ability')));
				});
				updateAllCategories();
			}

			function setSaving(isSaving) {
				requestInFlight = isSaving;
				$section.toggleClass('is-saving', isSaving);
				$allSwitches.prop('disabled', isSaving);
				if (isSaving) {
					$status.text(settings.i18n.aiAbilitiesSaving);
				}
			}

			function saveFailed(message) {
				saveQueued = false;
				applyDisabled(lastSavedDisabled);
				$status.text(message || settings.i18n.aiAbilitiesSaveFailed).addClass('is-error');
			}

			function saveAbilities() {
				var disabledIds;

				if (requestInFlight) {
					saveQueued = true;
					return;
				}

				disabledIds = collectDisabled();
				setSaving(true);
				$status.removeClass('is-error');

				$.ajax({
					url: settings.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'pa_save_ai_abilities',
						security: settings.nonce,
						disabled: JSON.stringify(disabledIds)
					}
				}).done(function (response) {
					if (!response.success) {
						saveFailed(response.data && response.data.message);
						return;
					}

					lastSavedDisabled = response.data.disabled_abilities || disabledIds;
					$status.text(response.data.message || '').removeClass('is-error');
				}).fail(function () {
					saveFailed(settings.i18n.aiAbilitiesSaveFailed);
				}).always(function () {
					setSaving(false);

					if (saveQueued) {
						saveQueued = false;
						saveAbilities();
					}
				});
			}

			function queueSave() {
				if (requestInFlight) {
					saveQueued = true;
					return;
				}

				clearTimeout(saveTimer);
				saveTimer = setTimeout(saveAbilities, 400);
			}

			lastSavedDisabled = collectDisabled();

			$categorySwitches.filter('[data-indeterminate="1"]').each(function () {
				this.indeterminate = true;
			});

			$section.on('change', '.pa-ai-ability-switch input[data-ability]', function () {
				updateCategory($(this).attr('data-cat'));
				queueSave();
			});

			$section.on('change', '.pa-ai-ability-cat-switch input[data-cat]', function () {
				var category = $(this).attr('data-cat'),
					checked = $(this).prop('checked');

				$abilitySwitches.filter('[data-cat="' + category + '"]').prop('checked', checked);
				updateCategory(category);
				queueSave();
			});

			$section.on('click', '.pa-ai-accordion-toggle', function () {

				var $btn = $(this),
					$body = $('#' + $btn.attr('aria-controls')),
					expanded = 'true' === $btn.attr('aria-expanded');

				if (expanded) {
					$btn.attr('aria-expanded', 'false');
					$body.attr('hidden', 'hidden');
					return;
				}

				// Accordion: collapse every other item first.
				$section.find('.pa-ai-accordion-toggle').attr('aria-expanded', 'false');
				$section.find('.pa-ai-accordion-body').attr('hidden', 'hidden');

				$btn.attr('aria-expanded', 'true');
				$body.removeAttr('hidden');
			});

			$section.on('click', '.pa-mcp-ability-cat-toggle', function () {

				var $btn = $(this),
					$body = $('#' + $btn.attr('aria-controls')),
					expanded = 'true' === $btn.attr('aria-expanded');

				if (expanded) {
					$btn.attr('aria-expanded', 'false');
					$body.attr('hidden', 'hidden');
					return;
				}

				// Accordion: collapse every other category first.
				$section.find('.pa-mcp-ability-cat-toggle').attr('aria-expanded', 'false');
				$section.find('.pa-mcp-ability-cat-body').attr('hidden', 'hidden');

				$btn.attr('aria-expanded', 'true');
				$body.removeAttr('hidden');
			});
		};

		// Handle settings form submission
		self.handleSettingsSave = function () {

			$("#pa-features .pa-section-info-cta input, #pa-ai-settings .pa-section-info-cta input, #pa-modules .pa-switcher input, #pa-modules .pa-section-info-cta input").on(
				'change',
				function () {

					if ('mini-cart' === $(this).attr('id')) {
						if ($(this).prop('checked')) {

							$("#pa_mc_temp").prop("checked", true);
							self.saveElementsSettings('elements', 'default', true);
						}
					} else if ('premium-ai-abilities' === $(this).attr('id')) {
						$('.pa-ai-accordion').prop('hidden', !$(this).prop('checked'));
						self.saveElementsSettings('elements', 'default');
					} else {
						self.saveElementsSettings('elements', 'default');
					}
				}
			)

			$("#pa-ver-control input, #pa-integrations input, #pa-ver-control input, #pa-integrations select").change(
				function () {
					self.saveElementsSettings('additional', 'default');
				}
			);

			$("#pa-integrations input[type=text]").on(
				'keyup',
				function () {
					self.saveElementsSettings('additional', 'default');
				}
			)

		};

		//get unused widgets.
		self.getUnusedWidget = function () {

			$.ajax(
				{
					url: settings.ajaxurl,
					type: 'POST',
					data: {
						action: 'pa_get_unused_widgets',
						security: settings.unused_nonce,
					},
					beforeSend: function () {
						$(".pa-btn-unused i").addClass("loading");
					},
					success: function (response) {
						console.log('unused widgets retrieved');

						self.unusedElements = response.data;

						$(".pa-btn-unused").removeClass("dimmed pa-fade").find("i").remove();

						if (shouldDisableUnused) {
							$('.pa-btn-unused').trigger('click');

							if (window.opener) {

								// $(".pa-btn-unused").find('span').text('Redirecting to Elementor!');

								swal.fire({
									title: 'Unused Widgets Disabled!',
									text: 'Click OK to close this window',
									type: 'success',
								}).then(function (result) {

									if (result.value) {
										window.close();
									}
								});

							}

						}


					},
					error: function (err) {
						console.log(err);
					}
				}
			);
		};

		self.disableElementorCustomTemplate = function () {
			$.ajax({
				url: settings.ajaxurl,
				type: "POST",
				data: {
					action: "pa_disable_elementor_mc_template",
					security: settings.nonce,
				},
				success: function (response) {
					console.log(response.data);
				},
				error: function (err) {
					console.log(err);
				},
			});
		}

		// Handle global enable/disable buttons
		self.handleElementsActions = function () {

			$(".pa-typed-search select").on(
				'change',
				function () {

					var filter = $(this).val(),
						$activeTab = $(".pa-switchers-container").not(".hidden");

					$activeTab.find(".pa-switcher").removeClass("hidden");

					if ('free' === filter) {
						$activeTab.find(".pro-element").addClass("hidden");
					} else if ('pro' === filter) {
						$activeTab.find(".pa-switcher").not(".pro-element").addClass("hidden");
					}
				}
			);

			$(".pa-elements-filter input").on(
				'keyup',
				function () {
					var filter = $(this).val(),
						$activeTab = $(".pa-switchers-container").not(".hidden"),
						currentQuerySwitchers = $activeTab.find(".pa-switcher");

					currentQuerySwitchers.addClass("hidden");
					var searchResults = currentQuerySwitchers.filter(function (index, switcher) {
						var elementName = $(switcher).find(".pa-element-name").text().toLowerCase();

						return -1 != elementName.indexOf(filter.toLowerCase()) ? $(switcher) : '';
					});

					searchResults.removeClass("hidden");
				}
			);

			// Enable/Disable all widgets
			$(".pa-btn-group").on(
				"click",
				'.pa-btn',
				function () {

					var $btn = $(this),
						isChecked = $btn.hasClass("pa-btn-enable"),
						customTemp = false;

					//If button is not already activated.
					if (!$btn.hasClass("active")) {
						$(".pa-btn-group .pa-btn").removeClass("active");
						$btn.addClass("active");

						$.ajax(
							{
								url: settings.ajaxurl,
								type: 'POST',
								data: {
									action: 'pa_save_global_btn',
									security: settings.nonce,
									isGlobalOn: isChecked
								}
							}
						);

					}

					//If it's enable all button.
					if (isChecked) {
						$(".pa-btn-group .pa-btn-unused").removeClass("dimmed");
						customTemp = true;
					} else {
						$(".pa-btn-group .pa-btn-unused").addClass("dimmed");
					}

					$("#pa-modules .pa-switcher input").prop("checked", isChecked);

					self.saveElementsSettings('elements', 'default', customTemp);

				}
			);

			//Disable unused widgets.
			$(".pa-btn-group").on(
				"click",
				'.pa-btn-unused:not(.dimmed)',
				function () {

					$.each(self.unusedElements, function (index, selector) {
						$('#pa-modules .pa-switcher.' + selector).find('input').prop('checked', false);
					});

					if (!shouldDisableUnused)
						$(this).addClass('dimmed');

					self.saveElementsSettings('elements', 'default');
				}
			);

			$("#pa-modules .pa-switcher input").on(
				'change',
				function () {
					var $this = $(this),
						id = $this.attr('id'),
						isChecked = $this.prop('checked');

					$("input[name='" + id + "']").prop('checked', isChecked);
				}
			)

			// Clear regenerated assets.
			$(".pa-section-info-cta").on(
				"click",
				'.pa-btn-regenerate',
				function () {

					var _this = $(this);
					_this.addClass("loading");

					$.ajax(
						{
							url: settings.ajaxurl,
							type: 'POST',
							data: {
								action: 'pa_clear_cached_assets',
								security: settings.generate_nonce,
							},
							success: function (response) {

								swal.fire({
									title: 'Generated Assets Cleared!',
									text: 'Click OK to continue',
									type: 'success',
									timer: 1500
								});

								_this.removeClass("loading");

							},
						}
					);
				}
			);

			// Clear saved site cursor settings.
			$('.pa-btn-clear-cursor').on('click', function () {
				var _this = $(this);
				_this.addClass("loading");

				$.ajax(
					{
						url: settings.ajaxurl,
						type: 'POST',
						data: {
							action: 'pa_clear_site_cursor_settings',
							security: settings.site_cursor_nonce,
						},
						success: function (response) {

							swal.fire({
								title: 'Site Cursor Cleared!',
								text: 'Click OK to continue',
								type: 'success',
								timer: 1500
							});

							_this.removeClass("loading");
							console.log(response)
						},
					}
				);
			});
		};

		self.handleSearchField = function () {

			var searchInput = url.searchParams.get("search");

			if (!searchInput)
				return;

			$(".pa-elements-filter input").val(searchInput).trigger("keyup");

		}

		self.handleActionField = function () {

			var action = url.searchParams.get("pa-action");

			if (!action)
				return;

			shouldDisableUnused = true;

			$('body,html').animate({
				scrollTop: $(".pa-btn-unused").offset().top - 100
			}, 700);

			$(".pa-btn-unused").toggleClass('dimmed pa-fade').find('span').text('Disabling Unused Widgets');

		};


		// Handle Tabs Elements
		self.initElementsTabs = function ($elem) {

			var $links = $elem.find('a'),
				$sections = $(".pa-switchers-container");

			$sections.eq(0).removeClass("hidden");
			$links.eq(0).addClass("active");

			$links.on(
				'click',
				function (e) {

					e.preventDefault();

					var $link = $(this),
						href = $link.attr('href');

					// Set this tab to active
					$links.removeClass("active");
					$link.addClass("active");

					// Navigate to tab section
					$sections.addClass("hidden");
					$("#" + href).removeClass("hidden");

				}
			);
		};

		// Handle settings tabs
		self.initNavTabs = function ($elem) {

			var $links = $elem.find('a'),
				$lastSection = null;

			$(window).on(
				'hashchange',
				function () {

					var hash = window.location.hash.match(new RegExp('tab=([^&]*)')),
						slug = hash ? hash[1] : $links.first().attr('href').replace('#tab=', ''),
						$link = $('#pa-tab-link-' + slug);

					// An unknown slug (e.g. a bookmark of a tab that no longer exists) would
					// otherwise leave every section hidden, i.e. a blank page.
					if (!$link.length) {
						slug = $links.first().attr('href').replace('#tab=', '');
						$link = $('#pa-tab-link-' + slug);
					}
					$links.removeClass('pa-section-active');
					$link.addClass('pa-section-active');

					// Hide the last active section
					if ($lastSection) {
						$lastSection.hide();
					}

					var $section = $('#pa-section-' + slug);
					$section.css(
						{
							display: 'block'
						}
					);

					$lastSection = $section;

				}
			).trigger('hashchange');

		};

		// Configure MCP Server: copy connection details + switch AI-client panels.
		self.initMcpConfig = function () {

			var $section = $('#pa-section-ai-abilities');

			if (!$section.length) {
				return;
			}

			// Copy text to the clipboard. Uses the async Clipboard API when the page
			// is a secure context, and falls back to execCommand for admin pages
			// served over plain http:// (where navigator.clipboard is unavailable).
			function paMcpCopyToClipboard(text) {

				if (navigator.clipboard && window.isSecureContext) {
					return navigator.clipboard.writeText(text);
				}

				return new Promise(function (resolve, reject) {

					var textarea = document.createElement('textarea');

					textarea.value = text;
					textarea.setAttribute('readonly', '');
					textarea.style.position = 'fixed';
					textarea.style.top = '0';
					textarea.style.left = '0';
					textarea.style.opacity = '0';

					document.body.appendChild(textarea);
					textarea.focus();
					textarea.select();

					var succeeded = false;

					try {
						succeeded = document.execCommand('copy');
					} catch (err) {
						succeeded = false;
					}

					document.body.removeChild(textarea);

					if (succeeded) {
						resolve();
					} else {
						reject();
					}
				});
			}

			// Copy the requested field or preformatted text to the clipboard.
			$section.on('click', '.pa-mcp-copy', function (e) {

				e.preventDefault();

				var $btn = $(this),
					target = document.getElementById($btn.attr('data-pa-mcp-copy'));

				if (!target) {
					return;
				}

				var isField = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA',
					text = isField ? target.value : target.textContent;

				paMcpCopyToClipboard(text).then(function () {

					var label = $btn.text();

					$btn.text($btn.attr('data-pa-mcp-copied') || 'Copied!');

					setTimeout(function () {
						$btn.text(label);
					}, 1500);
				}).catch(function () {

					// Last resort: select the text so the user can copy it manually.
					if (isField) {
						target.select();
					} else {
						var range = document.createRange();

						range.selectNodeContents(target);

						var selection = window.getSelection();

						selection.removeAllRanges();
						selection.addRange(range);
					}
				});
			});

			var $clientTabs = $section.find('.pa-mcp-client-tab'),
				$clientPanels = $section.find('.pa-mcp-client-panel');

			function selectMcpClient($tab) {

				var panelId = $tab.attr('data-pa-mcp-panel');

				$clientTabs.removeClass('is-active');
				$tab.addClass('is-active');
				$clientPanels.prop('hidden', true);
				$section.find('#' + panelId).prop('hidden', false);
			}

			$section.on('click', '.pa-mcp-client-tab', function () {
				selectMcpClient($(this));
			});

			$section.on('input', '#pa-mcp-alias', function () {

				var $alias = $(this),
					cleanAlias = $alias.val().replace(/[^A-Za-z0-9_-]/g, '');

				if (!cleanAlias) {
					cleanAlias = 'premium-addons';
				}

				$alias.val(cleanAlias);
				$section.find('.pa-mcp-alias').text(cleanAlias);
			});
		};

		self.handleRollBack = function () {

			// Rollback button
			$('.pa-rollback-button').on(
				'click',
				function (event) {

					event.preventDefault();

					var $this = $(this),
						href = $this.attr('href');

					if (!href) {
						return;
					}

					// Show PAPRO stable version if PAPRO Rollback is clicked
					var isPAPRO = '';
					if (-1 !== href.indexOf('papro_rollback')) {
						isPAPRO = 'papro_';
					}

					var premiumRollBackConfirm = premiumAddonsSettings.premiumRollBackConfirm;

					var dialogsManager = new DialogsManager.Instance();

					dialogsManager.createWidget(
						'confirm',
						{
							headerMessage: premiumRollBackConfirm.i18n.rollback_to_previous_version,
							message: premiumRollBackConfirm['i18n'][isPAPRO + 'rollback_confirm'],
							strings: {
								cancel: premiumRollBackConfirm.i18n.cancel,
								confirm: premiumRollBackConfirm.i18n.yes,
							},
							onConfirm: function () {

								$this.addClass('loading');

								location.href = $this.attr('href');

							}
						}
					).show();
				}
			);

		};

		/**
		 * Save elements settings.
		 * @param {String} action request action param.
		 * @param {String} source elements source, wizard|default (dashboard).
		 * @param {Boolean} updateCustomTemplate true if we need to update the Mini Cart custom template option.
		 * @param {String|null} redirectURL wizard redirection URL.
		 */
		self.saveElementsSettings = function (action, source, updateCustomTemplate = false, redirectURL) {

			var $form = null,
				defaultAddons = '';


			if (source === 'wizard') {
				if (settings.isSecondRun && Array.isArray(settings.savedFeatures)) {
					settings.savedFeatures.forEach(function (feature) {
						defaultAddons += `&${feature}=on`;
					});
				} else {
					defaultAddons = '&premium-assets-generator=on&premium-templates=on&premium-equal-height=on&premium-wrapper-link=on&pa-display-conditions=on&premium-duplicator';
				}
			}


			// We don't need to check the source as it'll always be 'wizard or default', so the 2nd part of the condition is always true.
			if (updateCustomTemplate) {
				defaultAddons += '&pa_mc_temp=on';
				self.disableElementorCustomTemplate();
			}

			if ('elements' === action) {
				// #pa-ai-settings must be in here: the save has full-replace semantics, so a
				// key missing from the POST is written back as disabled.
				$form = $('form#pa-settings, form#pa-features, form#pa-wz-settings, form#pa-ai-settings');
				action = 'pa_save_elements_settings';
			} else {
				$form = $('form#pa-ver-control, form#pa-integrations');
				action = 'pa_save_additional_settings';
			}

			$.ajax(
				{
					url: settings.ajaxurl,
					type: 'POST',
					data: {
						action: action,
						security: settings.nonce,
						fields: $form.serialize() + defaultAddons,
					},
					success: function () {
						console.log('settings saved');

						self.genButtonDisplay();
					},
					error: function (err) {
						console.log(err);
					},
					complete: function () {
						if (redirectURL) {
							window.location.href = redirectURL;
							window.location.reload();
						}
					}

				}
			);
		}

		self.genButtonDisplay = function () {
			var $form = $('form#pa-settings'),
				searchTerm = 'premium-assets-generator=on',
				indexOfFirst = $form.serialize().indexOf(searchTerm);

			if (indexOfFirst !== -1) {
				$('.pa-btn-generate').show();
			} else {
				$('.pa-btn-generate').hide();
			}
		};

		self.handlePaproActions = function () {

			$(".pro-slider").on(
				'click',
				function () {
					var isFeature = 'feature' === $(this).prev().attr('pa-element'),
						elementName = $(this).prev().attr('name').replace('premium-', '');

					var colorArr = ['#FF7800', '#6C9800', '#00BCF1', '#F7C230', '#006CE7'],
						redirectionLink = " https://premiumaddons.com/pro/?utm_source=" + elementName + "&utm_medium=wp-dash-pro&utm_campaign=get-pro&utm_term=" + settings.theme + '#get-pa-pro',
						iconClass = $(this).parent().prev().find('.pa-element-icon').attr('class'),
						iconColor = colorArr[Math.floor(Math.random() * colorArr.length)],
						demoLink = isFeature ? $(this).parents('.pa-section-outer-wrap').find('> a').attr('href') : $(this).parents('.pa-switcher').find('.pa-demo-link').attr('href'),
						eleTitle = isFeature ? $(this).parents('.pa-section-info-wrap').find('.pa-section-info > h4').text() : $(this).prev().attr('title') + ' Widget';

					// update icon.
					if (isFeature) {
						$('#pa-dash-pro-popup-cta').addClass('pa-feature-element');;

					} else {
						$('#pa-dash-pro-popup-cta').removeClass('pa-feature-element');
						$('#pa-dash-pro-popup-cta .pa-popup-widget-icon i').attr('class', iconClass).css('color', iconColor);
					}

					// update widget name.
					$('#pa-dash-pro-popup-cta .primary-des .pa-widget-name').text(eleTitle);

					// update CTA links.
					$('#pa-dash-pro-popup-cta .pa-popup-cta:first-child').attr('href', demoLink);
					$('#pa-dash-pro-popup-cta .pa-popup-cta:last-child').attr('href', redirectionLink);

					$('#pa-dash-pro-popup-cta').show().find('.popup-body').css('animation-name', 'swal2-show');
				}
			);

			$('.pa-popup-close').on('click', function () { self.closeProPopup(); });

			//Close popup when escape keyboard button is tapped.
			jQuery(document).on('keydown', function (e) {
				if (e.key === "Escape" || e.keyCode === 27) {
					self.closeProPopup();
				}
			});

			$(document).on('click', '#pa-dash-pro-popup-cta', function (e) {
				if ($(e.target).closest(".popup-body").length < 1) { self.closeProPopup(); }
			});

		};

		self.closeProPopup = function () {
			$('#pa-dash-pro-popup-cta .popup-body').css('animation-name', 'swal2-hide');

			setTimeout(() => {
				$('#pa-dash-pro-popup-cta').hide();
			}, 302);
		};

		self.handleWhiteLabelingAction = function () {
			// Trigger SWAL for White Labeling
			$(".premium-white-label-form.pro-inactive").on(
				'submit',
				function (e) {

					e.preventDefault();

					var redirectionLink = " https://premiumaddons.com/pro/?utm_source=wp-menu&utm_medium=wp-dash&utm_campaign=get-pro&utm_term=";

					Swal.fire(
						{
							title: '<span class="pa-swal-head">Enable White Labeling Options<span>',
							html: 'Premium Addons can be completely re-branded with your own brand name and author details. Your clients will never know what tools you are using to build their website and will think that this is your own tool set. White-labeling works as long as your license is active.',
							type: 'warning',
							showCloseButton: true,
							showCancelButton: true,
							cancelButtonText: "More Info",
							focusConfirm: true
						}
					).then(
						function (res) {
							// Handle More Info button
							if (res.dismiss === 'cancel') {
								window.open(redirectionLink + settings.theme, '_blank');
							}

						}
					);
				}
			);
		};

		self.handleNewsLetterForm = function () {

			$('.pa-newsletter-form').on('submit', function (e) {
				e.preventDefault();

				var email = $("#pa_news_email").val(),
					_this = this,
					isWizardForm = $(this).hasClass('pa-wizard-form');

				if (checkEmail(email)) {
					$.ajax(
						{
							url: settings.ajaxurl,
							type: 'POST',
							data: {
								action: 'subscribe_newsletter',
								security: settings.nonce,
								email: email
							},
							beforeSend: function () {
								console.log("Adding user to subscribers list");

								if (isWizardForm) {

									$(_this).find('.pa-wz-msg').remove();

									$(_this).animate({
										opacity: '0.45'
									}, 500);

									$(_this).find('.pa-btn').attr('disabled', 'disabled').find('.pa-wz-news-svg').hide();
									$(_this).find('.pa-btn .pa-wz-spinner').show();
								}
							},
							success: function (response) {
								if (response.data) {
									var status = response.data.status;

									if (status) {
										console.log("User added to subscribers list");

										if (isWizardForm) {
											$(_this).append('<span class="pa-wz-success pa-wz-msg">' + settings.i18n.successMsg + '</span>');
										} else {
											swal.fire({
												title: 'Thanks for subscribing!',
												text: 'Click OK to continue',
												type: 'success',
												timer: 1000
											});
										}
									}
								}

							},
							error: function (err) {
								console.log(err);

								if (isWizardForm) {
									$(_this).append('<span class="pa-wz-danger pa-wz-msg">' + settings.i18n.failMsg + '</span>');
								}
							},
							complete: function () {

								$(_this).find('.pa-btn').removeAttr('disabled').find('.pa-wz-spinner').hide();
								$(_this).find('.pa-btn .pa-wz-news-svg').show();

								$(_this).animate({
									opacity: '1'
								}, 100);
							}
						}
					);
				} else {
					Swal.fire({
						type: 'error',
						title: 'Invalid Email Address...',
						text: 'Please enter a valid email address!'
					});
				}

			})

		};

		function checkEmail(emailAddress) {
			var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
			return pattern.test(emailAddress);
		}

	};

	var instance = new PremiumAddonsNavigation();

	instance.init();

})(jQuery);
