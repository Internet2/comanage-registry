$.widget("ui.autocomplete", $.ui.autocomplete, {
  options: {
    maxMenuHeight: 300,
    metaNote: "Scroll for more results.",
    itemsVisible: 11 // how many items should be visible without scrolling
  },

  _renderMenu: function(ul, items) {
    var that = this;

    // Regenerate the whole menu: meta header + spacer + items
    $(ul).empty();

    var total = items.length;

    // Meta header (accessible, non-interactive)
    var $meta = $("<li class='cm-ac-meta ui-state-disabled' role='status' aria-live='polite' aria-atomic='true'></li>");
    var $count = $("<span class='cm-ac-count' aria-hidden='true'></span>");
    var $note = $("<span class='cm-ac-note' role='note'></span>").text(this.options.metaNote);
    var $sr = $("<span class='sr-only' aria-hidden='false'></span>");
    $meta.append($count, $note, $sr).appendTo(ul);

    // Spacer under meta so items don’t slide under sticky header
    var $spacer = $("<li class='cm-ac-spacer' aria-hidden='true'></li>")
      .css({
        margin: 0,
        padding: 0,
        border: 0,
        visibility: "hidden",
        display: "block",
        listStyle: "none"
      })
      .appendTo(ul);

    // Render items (li.ui-menu-item > div.cm-ac-item-wrapper.ui-menu-item-wrapper)
    $.each(items, function(index, item) {
      that._renderItemData(ul, item);
    });

    // Measure and set spacer height to meta height
    var $menu = $(ul);
    var metaH = $meta.outerHeight();
    $spacer.height(metaH);

    // Initialize x/total with first item if any
    var $lis = $menu.find("li").filter(function() {
      return !$(this).hasClass("cm-ac-meta") && !$(this).hasClass("cm-ac-spacer");
    });
    var initialIndex = $lis.length ? 1 : 0;
    this._updateMetaRow($menu, initialIndex, total);

    // Recompute on next frame in case layout shifts
    var self = this;
    requestAnimationFrame(function() {
      $spacer.height($meta.outerHeight());
      // After heights settle, size menu to fit N items
      self._resizeMenu();
    });

    // Update x/total on keyboard focus and mouse hover
    $menu.off(".cmAcMeta");
    $menu.on("menufocus.cmAcMeta", function(e, ui) {
      var $itemLi = ui && ui.item ? ui.item : null;
      if ($itemLi && $itemLi.length && !$itemLi.hasClass("cm-ac-meta") && !$itemLi.hasClass("cm-ac-spacer")) {
        var idx = $lis.index($itemLi) + 1;
        self._updateMetaRow($menu, idx, total);
      }
    });
    $menu.on("mouseenter.cmAcMeta", "li", function() {
      var $li = $(this);
      if ($li.hasClass("cm-ac-meta") || $li.hasClass("cm-ac-spacer")) return;
      var idx = $lis.index($li) + 1;
      self._updateMetaRow($menu, idx, total);
    });
  },

  _renderItem: function(ul, item) {
    // Build inner wrapper with both classes so it matches the structure
    let $wrapper = $("<div class='cm-ac-item-wrapper ui-menu-item-wrapper' tabindex='-1'></div>");
    $wrapper.append($("<div class='cm-ac-name'></div>").text(item.label));

    if (item?.emailShort !== '' && item?.emailShort !== undefined) {
      $wrapper.append(
        $("<div class='cm-ac-subitem cm-ac-email'></div>")
          .append($("<span class='cm-ac-label'></span>").text(item.emailLabel))
          .append(document.createTextNode(item.emailShort))
      );
    }

    if (item?.identifierShort !== '' && item?.identifierShort !== undefined) {
      $wrapper.append(
        $("<div class='cm-ac-subitem cm-ac-id'></div>")
          .append($("<span class='cm-ac-label'></span>").text(item.identifierLabel))
          .append(document.createTextNode(item.identifierShort))
      );
    }

    // li with ui-menu-item class
    return $("<li class='ui-menu-item' role='presentation'></li>")
      .append($wrapper)
      .appendTo(ul);
  },

  _resizeMenu: function() {
    var ul = this.menu.element;
    var inputWidth = this.element.outerWidth();

    // Compute dynamic max-height to fit N items + header
    var $menu = ul;
    var $meta = $menu.find("li.cm-ac-meta");
    var $spacer = $menu.find("li.cm-ac-spacer");
    var $firstItem = $menu.find("li.ui-menu-item").first();

    // Fallback heights if not available yet
    var metaH = $meta.outerHeight() || 0;
    var spacerH = $spacer.outerHeight() || metaH;
    var itemH = $firstItem.outerHeight() || 32; // a reasonable default row height

    var visibleCount = parseInt(this.options.itemsVisible, 11) || 11;
    var maxH = metaH + spacerH + (itemH * visibleCount);

    ul.css({
      width: inputWidth,
      "max-width": inputWidth,
      "box-sizing": "border-box",
      "max-height": maxH + "px",
      "overflow-y": "auto",
      "overflow-x": "hidden",
      position: "relative"
    });
  },

  _updateMetaRow: function($menu, index, total) {
    var $meta = $menu.find("li.cm-ac-meta");
    var $count = $meta.find(".cm-ac-count");
    var $sr = $meta.find(".sr-only");
    var safeIndex = Math.max(0, index || 0);
    $count.text(safeIndex + "/" + total + " items");
    $sr.text("Item " + safeIndex + " of " + total + ". Scroll for more results.");
  }
});
