
STUDIP.Fleximport = {
    "updateTable": function (payload) {
        var table_id = payload.table_id;
        var name = payload.name;
        var html = payload.html;
        if (html) {
            if (jQuery("#table_" + table_id + "_container").length > 0) {
                jQuery("#table_" + table_id + "_container").replaceWith(html);
            } else {
                var inserted = false;
                jQuery("#process_form > .tablecontainer").each(function () {
                    if (name.localeCompare(jQuery(this).data("name")) < 0) {
                        jQuery(html).insertBefore(this);
                        inserted = true;
                    }
                });
                if (!inserted) {
                    jQuery("#process_form").append(html);
                }
                jQuery("#table_" + table_id + "_container").hide().delay(500).fadeIn();
            }
        } else {
            jQuery("#table_" + table_id + "_container").fadeOut(function () { jQuery(this).remove(); });
        }
        window.setTimeout(STUDIP.Dialog.close, 100);
    },
    "deleteTable": function (table_id) {
        jQuery.ajax({
            "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/fleximport/setup/removetable/" + table_id,
            "type": "post",
            "success": function () {
                jQuery("#table_" + table_id + "_container").fadeOut(function () {
                    jQuery(this).remove();
                });
            }
        });
    }
};