
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
        if (payload.close) {
            window.setTimeout(STUDIP.Dialog.close, 100);
        }
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
    },
    "changeMappingOfField": function (field) {
        jQuery('#simplematching_' + field + '_static').toggle(this.value === 'static value');
        jQuery('#simplematching_' + field + '_mapfrom').toggle(
            (this.value.indexOf('fleximport_mapper__') === 0) || (this.value.indexOf('fleximportkeyvalue_') === 0)
        );
        jQuery('#simplematching_' + field + '_format').toggle(this.value !== '');
        jQuery('#simplematching_' + field + '_delimiter').css('display', !this.value ? 'none' : 'flex');
        jQuery('#simplematching_' + field + '_foreignkey_sormclass').toggle(this.value === "fleximport_mapper__FleximportForeignKeyMapper__fleximport_foreign_key");
    },
    "showProgress": function () {
        let displayTime = function (seconds) {
            let hours = Math.floor(seconds / 60 / 60);
            let minutes = Math.floor((seconds - (hours * 60)) / 60);
            seconds = seconds - (minutes * 60) - (hours * 60 * 60);
            if (hours > 0) {
                return hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + " Stunden";
            } else if (minutes > 0) {
                return minutes + ":" + (seconds < 10 ? "0" + seconds : seconds) + " Minuten";
            } else {
                return seconds + " Sekunden";
            }
        };

        if ($(this).data("duration") < 2) {
            return true;
        }

        let importing = $(this).is("button");
        let title = jQuery("#waiting_window").data(importing ? "title_process" : "title_fetch");
        let start = Date.now();
        jQuery("#waiting_window .recent").text(
            displayTime(0)
        );
        jQuery("#waiting_window .last").text(
            displayTime($(this).data("duration"))
        );
        jQuery("#waiting_window .bar").progressbar({
            "max": $(this).data("duration") * 1000 * 1.05
        });
        window.setInterval(function () {
            let now = Date.now();
            let milliseconds = now - start;
            if (milliseconds <= jQuery("#waiting_window .bar").progressbar("option", "max")) {
                jQuery("#waiting_window .bar").progressbar("option", "value", milliseconds);
            } else {
                jQuery("#waiting_window .bar").progressbar("option", "value", jQuery("#waiting_window .bar").progressbar("option", "max"));
            }
            jQuery("#waiting_window .recent").text(
                displayTime(Math.floor((now - start) / 1000))
            );
        }, 100);
        window.setTimeout(function () {
            jQuery("#waiting_window").dialog({
                "title": title,
                "modal": true
            });
        }, 1000);
        return true;
    }
};

$(function () {
    $('.tablecontainer.uploadable').on('dragover dragleave', (event) => {
        $(event.target).closest('.tablecontainer').toggleClass('hovered', event.type === 'dragover');
        event.preventDefault();
    }).on('drop', (event) => {
        var filelist = event.originalEvent.dataTransfer.files || {};
        $(event.target).closest('.tablecontainer').toggleClass('hovered', false);
        let table_id = $(event.target).closest('.tablecontainer').data('table_id');
        let process_id = $(event.target).closest('form').data('process_id');
        console.log($(event.target).closest('.tablecontainer').find("input[type=file]"));

        let data = new FormData();
        data.append('tableupload[' + table_id + ']', filelist[0], "upload.csv");

        $.ajax({
            url: STUDIP.URLHelper.getURL('plugins.php/fleximport/import/process/' + process_id),
            data: data,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST'
        }).done(json => {
            window.location.reload();
        });

        event.preventDefault();
    });
});
