/**
 * fm-variation-buttons.js
 * -----------------------------------------------------------------------------
 * WHAT IT DOES
 * -----------------------------------------------------------------------------
 * Replaces WooCommerce variation <select> dropdowns with accessible, keyboard-
 * navigable "radio button" style buttons. Keeps Woo events intact and plays
 * nicely with Tiered Pricing plugins because it always triggers the native
 * <select> "change" event when a button is activated.
 *
 * Works for:
 * - Standard single-product variation forms (.variations_form)
 * - Pages that inject product forms later (quick-view modals, AJAX fragments)
 *
 * -----------------------------------------------------------------------------
 * HOW TO USE (SNIPPET / LEGO BLOCK STYLE)
 * -----------------------------------------------------------------------------
 * 1) Import/compile into your main JS bundle.
 *    Example (ESBuild/Vite/Webpack):
 *      import './path/to/fm-variation-buttons.js';
 *
 * 2) Make sure Woo's variation script is present on product pages.
 *    (Usually Woo does this automatically. If needed, ensure:
 *      'wc-add-to-cart-variation' is enqueued on single product.)
 *
 * 3) Build your assets as usual (this snippet self-initializes on DOMReady).
 *
 * 4) If your theme opens quick-views / injects forms dynamically,
 *    call the refresh method after injection:
 *      window.VariationButtons && window.VariationButtons.refresh($container);
 *    Examples:
 *      // When a quick-view modal finishes rendering:
 *      jQuery(document).on('qv_loader_stop shown.bs.modal', function (e) {
 *        var $root = jQuery(document); // or a scoped container for speed
 *        window.VariationButtons && window.VariationButtons.refresh($root);
 *      });
 *
 *    (You can also call refresh() after 'wc_fragments_loaded' or any custom
 *     event that adds a .variations_form to the DOM.)
 *
 * -----------------------------------------------------------------------------
 * OPTIONAL SETTINGS (pass once, before first use)
 * -----------------------------------------------------------------------------
 * You can override classes/behavior by calling:
 *      window.VariationButtons && window.VariationButtons.init({
 *        buttonClass: 'fm-var-btn',
 *        selectedClass: 'is-selected',
 *        disabledClass: 'is-disabled',
 *        buttonGroupClass: 'fm-variation-button-group',
 *        containerClass: 'fm-variation-buttons',
 *        initFlagClass: 'fm-variations-form-initialized',
 *        ensureSelection: true,       // auto-pick first valid option
 *        hideOriginalSelect: true,    // visually hide original <select>
 *        hideWithClass: ''            // e.g. 'u-visually-hidden' (else inline)
 *      });
 * If you don’t call init() yourself, defaults are used and auto-init runs.
 *
 * -----------------------------------------------------------------------------
 * CSS HOOKS / STARTER STYLES
 * -----------------------------------------------------------------------------
 * The HTML looks like:
 *   <div class="fm-variation-buttons" data-attribute_name="attribute_pa_size">
 *     <div class="fm-variation-button-group" role="radiogroup" aria-label="Product variation options">
 *       <button class="fm-var-btn" role="radio" data-value="small" aria-checked="false" tabindex="-1">Small</button>
 *       <button class="fm-var-btn is-selected" role="radio" data-value="medium" aria-checked="true" tabindex="0">Medium</button>
 *       <button class="fm-var-btn is-disabled" role="radio" data-value="large" aria-checked="false" aria-disabled="true" tabindex="-1">Large</button>
 *     </div>
 *   </div>
 *
 * Minimal starter CSS (adjust to your design system):
 *
 *   .fm-variation-button-group { display: flex; gap: .5rem; flex-wrap: wrap; }
 *   .fm-var-btn {
 *     border: 1px solid #ccc; padding: .5rem .75rem; background: #fff; cursor: pointer;
 *   }
 *   .fm-var-btn.is-selected { outline: 2px solid currentColor; border-color: currentColor; }
 *   .fm-var-btn.is-disabled { opacity: .45; cursor: not-allowed; }
 *   /* Focus visibility */
 *   .fm-var-btn:focus { outline: 2px solid #000; outline-offset: 2px; }
 *
 * -----------------------------------------------------------------------------
 * ACCESSIBILITY
 * -----------------------------------------------------------------------------
 * - Uses the WAI-ARIA Radio Group pattern: role="radiogroup" + role="radio".
 * - Keyboard: ← → ↑ ↓ move focus; Home/End jump ends; Space/Enter select.
 * - Roving tabindex: only the current item is tabbable (tabindex="0").
 * - The original <select> remains in the DOM (visually hidden by default)
 *   and is kept in sync for maximum plugin compatibility.
 *
 * -----------------------------------------------------------------------------
 * TIERED PRICING COMPATIBILITY
 * -----------------------------------------------------------------------------
 * - On selection, we set the <select> value and trigger('change'),
 *   which is exactly what those plugins listen for. No extra wiring needed.
 * - If Tiered Pricing isn’t active, nothing breaks—Woo still updates price/availability.
 *
 * -----------------------------------------------------------------------------
 * COMMON “REFRESH” HOOKS (choose any that apply to your stack)
 * -----------------------------------------------------------------------------
 *   jQuery(document.body).on('wc_fragments_loaded', function () {
 *     window.VariationButtons && window.VariationButtons.refresh(jQuery(document));
 *   });
 *   jQuery(document).on('quick-view:opened modal:shown ajax:product:inserted', function (e, $scope) {
 *     window.VariationButtons && window.VariationButtons.refresh($scope || jQuery(document));
 *   });
 *
 * -----------------------------------------------------------------------------
 * UNDO / TEAR DOWN (RARE)
 * -----------------------------------------------------------------------------
 *   window.VariationButtons && window.VariationButtons.destroy(jQuery('.variations_form'));
 *
 * -----------------------------------------------------------------------------
 * TROUBLESHOOTING
 * -----------------------------------------------------------------------------
 * - If buttons don’t show: confirm .variations_form exists and that Woo’s
 *   variation form JS ran (we auto-init if available, and we rAF-poll as fallback).
 * - If styles look off: confirm your CSS is loaded after this renders (or use higher specificity).
 * - If selects shouldn’t be visually hidden, set { hideOriginalSelect: false }.
 */

/* Reusable, accessible Woo variation buttons
 * - Configurable classes & behavior
 * - Robust Woo init waiting (rAF loop)
 * - Per-form lifecycle (build/refresh/destroy)
 * - A11y: radiogroup/radio, aria-checked, roving tabindex, keyboard support
 * - Uses WeakMap to map selects <-> containers to avoid leaks
 */

/* fm-variation-buttons.js
 * Reusable, accessible Woo variation buttons
 * - Configurable classes & behavior
 * - Robust Woo init waiting (rAF loop)
 * - Per-form lifecycle (build/refresh/destroy)
 * - A11y: radiogroup/radio, aria-checked, roving tabindex, keyboard (← → ↑ ↓ Home End Space Enter)
 * - Uses WeakMap to map selects <-> containers to avoid leaks
 */
(function ($, window, document, undefined) {
	"use strict";

	var defaults = {
		buttonClass: "fm-var-btn",
		selectedClass: "is-selected",
		disabledClass: "is-disabled",
		buttonGroupClass: "fm-variation-button-group",
		containerClass: "fm-variation-buttons",
		initFlagClass: "fm-variations-form-initialized",
		ensureSelection: true,
		hideOriginalSelect: true,
		// If you prefer a utility class for hiding the native select, set this.
		hideWithClass: "", // e.g. "u-visually-hidden"; empty = inline styles
	};

	// Private maps to avoid DOM walking and memory leaks
	var selectToContainer = new WeakMap();
	var containerToSelect = new WeakMap();

	var VariationButtons = {
		settings: $.extend({}, defaults),
		_bound: false,

		init: function (options) {
			if (options) {
				this.settings = $.extend({}, defaults, this.settings, options);
			}
			this.bindEvents();
			this.initializeForms($(document));
			return this;
		},

		// Re-run on dynamically injected content (quick-view, AJAX fragments)
		refresh: function ($root) {
			this.initializeForms($root || $(document));
		},

		bindEvents: function () {
			if (this._bound) return; // guard double-binding
			this._bound = true;
			var self = this;

			// Click (activation)
			$(document).on(
				"click.variationButtons",
				"." + this.settings.buttonClass,
				function (e) {
					e.preventDefault();
					self.handleButtonActivate($(this));
				}
			);

			// Keyboard interaction per WAI-ARIA Radio Group pattern
			$(document).on(
				"keydown.variationButtons",
				"." + this.settings.buttonClass,
				function (e) {
					self.handleKeydown(e, $(this));
				}
			);

			// Woo events to reflect state/availability
			$(document).on(
				"change.variationButtons found_variation.variationButtons reset_data.variationButtons woocommerce_update_variation_values.variationButtons",
				".variations_form",
				function () {
					var $form = $(this);
					self.updateAllButtons($form);
					self.syncDisabledStates($form);
				}
			);
		},

		initializeForms: function ($root) {
			var self = this;
			$root.find(".variations_form").each(function () {
				var $form = $(this);
				self.ensureWooInit($form, function () {
					self.buildButtons($form);
					self.updateAllButtons($form);
					if (self.settings.ensureSelection) {
						self.ensureDefaultSelected($form);
					}
					self.syncDisabledStates($form);
				});
			});
		},

		ensureWooInit: function ($form, callback) {
			var s = this.settings;
			// Fast path: already initialized/flagged
			if (
				$form.data("product_variations") ||
				$form.hasClass(s.initFlagClass)
			) {
				callback();
				return;
			}
			// Initialize if available
			if (typeof $form.wc_variation_form === "function") {
				$form.wc_variation_form();
			}
			// Poll via rAF until Woo has populated state
			var tryReady = function () {
				if (
					$form.data("product_variations") ||
					$form.find(".single_variation").length
				) {
					$form.addClass(s.initFlagClass);
					callback();
				} else {
					requestAnimationFrame(tryReady);
				}
			};
			tryReady();
		},

		buildButtons: function ($form) {
			var s = this.settings;
			var self = this;

			$form.find(".variations select").each(function () {
				var $select = $(this);

				if ($select.data("fm-buttons-built")) return;
				var name = $select.attr("name");
				if (!name) return; // defensive

				var $container = self.createContainer(name);
				var $group = $container.find("." + s.buttonGroupClass);

				$select.find("option").each(function () {
					var $opt = $(this);
					var val = $opt.attr("value");
					var label = ($opt.text() || "").trim();
					if (!val || !label) return;
					$group.append(self.createButton(val, label));
				});

				if ($group.children().length === 0) {
					$container.remove();
					return;
				}

				$select.after($container);

				if (s.hideOriginalSelect) {
					self.hideSelect($select);
				}

				// Cache relationships
				selectToContainer.set($select[0], $container[0]);
				containerToSelect.set($container[0], $select[0]);

				$select.data("fm-buttons-built", true);
			});
		},

		createContainer: function (attrName) {
			var s = this.settings;
			var $container = $("<div>")
				.addClass(s.containerClass)
				.attr("data-attribute_name", attrName);

			var $group = $("<div>").addClass(s.buttonGroupClass).attr({
				role: "radiogroup",
				"aria-label": "Product variation options",
			});

			$container.append($group);
			return $container;
		},

		createButton: function (value, label) {
			var s = this.settings;
			return $("<button>")
				.addClass(s.buttonClass)
				.attr({
					type: "button",
					role: "radio",
					"data-value": value,
					"aria-checked": "false",
					tabindex: "-1",
				})
				.text(label);
		},

		hideSelect: function ($select) {
			var s = this.settings;
			if (s.hideWithClass) {
				$select
					.addClass(s.hideWithClass)
					.attr({ "aria-hidden": "true", tabindex: "-1" });
				return;
			}
			$select
				.css({
					position: "absolute",
					left: "-9999px",
					width: "1px",
					height: "1px",
					overflow: "hidden",
					clip: "rect(1px, 1px, 1px, 1px)",
					"clip-path": "inset(50%)",
				})
				.attr({ "aria-hidden": "true", tabindex: "-1" });
		},

		// Activation by mouse or keyboard
		handleButtonActivate: function ($button) {
			var s = this.settings;
			if ($button.hasClass(s.disabledClass)) return;

			var containerEl = $button.closest("." + s.containerClass)[0];
			var selectEl = containerToSelect.get(containerEl);
			if (!selectEl) return;
			var $select = $(selectEl);

			var value = $button.data("value");
			var alreadySelected = $button.hasClass(s.selectedClass);

			if (alreadySelected) {
				$select.trigger("change"); // retrigger listeners (e.g., tiered pricing)
				return;
			}

			this.updateButtonSelection($(containerEl), $button);
			$select.val(value).trigger("change");
		},

		updateButtonSelection: function ($container, $selected) {
			var s = this.settings;
			var $btns = $container.find("." + s.buttonClass);
			$btns
				.removeClass(s.selectedClass)
				.attr("aria-checked", "false")
				.attr("tabindex", "-1");
			$selected
				.addClass(s.selectedClass)
				.attr("aria-checked", "true")
				.attr("tabindex", "0")
				.focus();
		},

		handleKeydown: function (e, $button) {
			var s = this.settings;
			var key = e.key;
			var $container = $button.closest("." + s.containerClass);
			var $buttons = $container.find(
				"." + s.buttonClass + ":not(." + s.disabledClass + ")"
			);
			if ($buttons.length === 0) return;

			var idx = $buttons.index($button);
			var targetIdx = idx;
			var move = false;

			switch (key) {
				case "ArrowRight":
				case "ArrowDown":
					targetIdx = (idx + 1) % $buttons.length;
					move = true;
					break;
				case "ArrowLeft":
				case "ArrowUp":
					targetIdx = (idx - 1 + $buttons.length) % $buttons.length;
					move = true;
					break;
				case "Home":
					targetIdx = 0;
					move = true;
					break;
				case "End":
					targetIdx = $buttons.length - 1;
					move = true;
					break;
				case " ": // Space
				case "Enter":
					e.preventDefault();
					this.handleButtonActivate($button);
					return;
				default:
					return; // ignore other keys
			}

			if (move) {
				e.preventDefault();
				var $target = $($buttons.get(targetIdx));
				// Roving focus without selection per ARIA pattern
				$container.find("." + s.buttonClass).attr("tabindex", "-1");
				$target.attr("tabindex", "0").focus();
			}
		},

		updateAllButtons: function ($form) {
			var self = this;
			$form.find(".variations select").each(function () {
				self.reflectSelect($(this));
			});
		},

		reflectSelect: function ($select) {
			var s = this.settings;
			var containerEl = selectToContainer.get($select[0]);
			if (!containerEl) return;
			var $container = $(containerEl);
			var value = $select.val() || "";

			var $buttons = $container.find("." + s.buttonClass);
			var found = false;
			$buttons.each(function () {
				var $b = $(this);
				var match =
					String($b.data("value")) == String(value) && value !== "";
				$b.toggleClass(s.selectedClass, match).attr(
					"aria-checked",
					match ? "true" : "false"
				);
				if (match) {
					$b.attr("tabindex", "0");
					found = true;
				} else {
					$b.attr("tabindex", "-1");
				}
			});
			// If nothing selected, put focusable 0 on the first enabled button
			if (!found) {
				$buttons
					.filter(":not(." + s.disabledClass + ")")
					.attr("tabindex", "-1")
					.first()
					.attr("tabindex", "0");
			}
		},

		syncDisabledStates: function ($form) {
			var s = this.settings;
			$form.find(".variations select").each(function () {
				var $select = $(this);
				var containerEl = selectToContainer.get($select[0]);
				if (!containerEl) return;
				var $container = $(containerEl);

				$container.find("." + s.buttonClass).each(function () {
					var $b = $(this);
					var val = $b.data("value");
					var $opt = $select.find('option[value="' + val + '"]');
					var disabled = $opt.length === 0 || $opt.is(":disabled");
					$b.toggleClass(s.disabledClass, disabled).attr(
						"aria-disabled",
						disabled ? "true" : "false"
					);
					if (disabled && $b.attr("tabindex") === "0") {
						$b.attr("tabindex", "-1");
					}
				});
			});
		},

		ensureDefaultSelected: function ($form) {
			var self = this;
			$form.find(".variations select").each(function () {
				var $select = $(this);
				if ($select.val()) {
					self.reflectSelect($select);
					return;
				}
				var $first = $select
					.find("option:not(:disabled)")
					.filter(function () {
						return $(this).attr("value") !== "";
					})
					.first();
				if ($first.length) {
					$select.val($first.attr("value")).trigger("change");
				}
			});
		},

		destroy: function ($form) {
			var self = this;
			$form = $form || $(".variations_form");

			$form.each(function () {
				var $current = $(this);
				$current
					.find("." + self.settings.containerClass)
					.each(function () {
						var containerEl = this;
						var selectEl = containerToSelect.get(containerEl);
						if (selectEl) {
							selectToContainer.delete(selectEl);
						}
						containerToSelect.delete(containerEl);
					});
				$current.find("." + self.settings.containerClass).remove();

				$current.find(".variations select").each(function () {
					var $select = $(this);
					if (self.settings.hideWithClass) {
						$select.removeClass(self.settings.hideWithClass);
					}
					$select.css({
						position: "",
						left: "",
						width: "",
						height: "",
						overflow: "",
						clip: "",
						"clip-path": "",
					});
					$select.removeAttr("aria-hidden").removeAttr("tabindex");
					$select.removeData("fm-buttons-built");
				});

				$current.removeClass(self.settings.initFlagClass);
			});

			$(document).off(".variationButtons");
			this._bound = false;
		},
	};

	// Auto-init on DOM ready
	$(function () {
		window.VariationButtons = VariationButtons.init();
	});
})(jQuery, window, document);


