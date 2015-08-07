var SideMenu = function (pageOptions) {

    this.options = {
        "itemDivs"      : $("#menu #wrapper div"),
        "menuOut"       : false,
        "menuCurrent"   : 'Home'
    };

    this.initialize = function (pageOptions) {
        console.dir(pageOptions);
        this.options = $.extend(this.options, JSON.parse(pageOptions));
        this.options.menuOut = (this.options.menuOut == "1");

        this.getCurrent();
    };

    this.getCurrent = function () {
        var _self = this;
        this.options.itemDivs.each(function (idx, div) {
            if (div.id == 'menu'+_self.options.menuCurrent) {
                $(div).addClass('current');
            }
        });
    };

    this.moveOut = function () {};
    this.moveIn = function () {};

    return this.initialize(pageOptions);
};

$(function () {
    var sideMenu = new SideMenu(pageOptions);
});