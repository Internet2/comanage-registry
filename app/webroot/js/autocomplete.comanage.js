$.widget( "ui.autocomplete", $.ui.autocomplete, {
  _renderMenu: function( ul, items ) {
    var that = this;
    $.each( items, function( index, item ) {
      that._renderItemData( ul, item );
    });
  },
  _renderItem: function( ul, item ) {
    let itemMarkup = '<div class="cm-ac-item-wrapper">';
    itemMarkup += '<div class="cm-ac-name">' + item.label + '</div>';
    if(item?.emailShort != '' && item?.emailShort != undefined) {
      itemMarkup += '<div class="cm-ac-subitem cm-ac-email"><span class="cm-ac-label">' + item.emailLabel + '</span>' + item.emailShort + '</div>';
    }
    if(item?.identifierShort != '' && item?.identifierShort != undefined) {
      itemMarkup += '<div class="cm-ac-subitem cm-ac-id"><span class="cm-ac-label">' + item.identifierLabel + '</span>' + item.identifierShort + '</div>';
    }
    itemMarkup += '</div>';

    return $("<li>").append(itemMarkup).appendTo(ul);
  }
});