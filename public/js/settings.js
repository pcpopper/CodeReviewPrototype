var SettingsGroupToggle = function () {

    this.initialize = function () {
        this.bindElements();
    };

    this.bindElements = function () {
        var bindClass = $(".header");

        bindClass.bind("click", this.toggleFile);
    };

    this.toggleFile = function (event) {
        var holder = $('#'+event.target.id+'Settings');

        if (holder.data('collapsed') == 0) {
            holder.slideUp();
            holder.data('collapsed', 1);
        } else {
            holder.slideDown();
            holder.data('collapsed', 0);
        }
    };

    return this.initialize();
};

var SettingsGroupSave = function () {
    this.saveButtons = $('.sectionSave');

    this.initialize = function () {
        this.bindElements();
    };

    this.bindElements = function () {
        this.saveButtons.bind("click", this.saveSettings);
    };

    this.saveSettings = function (event) {
        var settings = {'section' : $(event.target).data('section')};
        $("." + $(event.target).data('section')).each(function (idx, input) {
            settings[input.id] = $(input).val();
        });

        $.ajax({
            method: "POST",
            url: "/settings/save",
            data: settings
        })
        .done(function () {
            //
        });
    };

    return this.initialize();
};

$(function () {
    var settingsGroupSave = new SettingsGroupSave();
    var settingsGroupToggle = new SettingsGroupToggle();
});