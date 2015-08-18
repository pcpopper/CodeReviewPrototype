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

$(function () {
    var settingsGroupToggle = new SettingsGroupToggle();
});