var SideMenu = function (pageOptions) {

    this.itemDivs = $("#menu #wrapper div");
    this.menuCollapseDiv = $('#menu #button');
    this.menuDiv = $('#menu');
    this.pageWrapper = $('#pageWrapper');

    this.options = {
        "menuOut"       : false,
        "menuCurrent"   : 'Home',
        "newMargin"     : 0,
        "origMargin"    : 0,
        "newPadding"    : 0,
        "oldPadding"    : 0
    };

    this.initialize = function (pageOptions) {
        var _self = this;
        this.options = $.extend(this.options, JSON.parse(pageOptions));
        this.options.menuOut = (this.options.menuOut == "1");
        this.options.newMargin = this.menuDiv.width() + parseInt(this.menuDiv.css('margin-left').replace('px', ''));
        this.options.origMargin = - parseInt(this.menuDiv.css('margin-left').replace('px', ''));
        this.options.oldPadding = parseInt(this.pageWrapper.css('padding-left').replace('px', ''));
        this.options.newPadding = - (this.menuDiv.width() - 16 - parseInt(this.pageWrapper.css('padding-left').replace('px', '')));

        this.toggleMenu();

        setTimeout(function () {
            _self.menuDiv.addClass('transition');
            _self.pageWrapper.addClass('transition');
        },500);

        this.getCurrent();
        this.bindElements();
    };

    this.getCurrent = function () {
        var _self = this;
        this.itemDivs.each(function (idx, div) {
            if (div.id.toLowerCase() == 'menu'+_self.options.menuCurrent.toLowerCase()) {
                $(div).addClass('current');
            }
        });
    };

    this.bindElements = function() {
        this.menuCollapseDiv.bind("click", {
            "newMargin"         : this.options.newMargin,
            "origMargin"        : this.options.origMargin,
            "newPadding"        : this.options.newPadding,
            "oldPadding"        : this.options.oldPadding,
            "menuDiv"           : this.menuDiv,
            "menuCollapseDiv"   : this.menuCollapseDiv,
            "pageWrapper"       : this.pageWrapper
        }, this.toggleMenu);
    };

    this.toggleMenu = function (event) {
        if (event) {
            this.options = {
                "newMargin"     : event.data.newMargin,
                "origMargin"    : event.data.origMargin,
                "newPadding"    : event.data.newPadding,
                "oldPadding"    : event.data.oldPadding
            };
            this.pageWrapper = $(event.data.pageWrapper);
            this.menuDiv = $(event.data.menuDiv);
            this.options.menuOut = this.menuDiv.data('collapsed');
            this.menuCollapseDiv = $(event.data.menuCollapseDiv);
        }

        if (this.options.menuOut) {
            this.menuDiv.css('margin-left', - this.options.origMargin);
            this.menuCollapseDiv.html('<');
            this.menuDiv.data('collapsed', 0);
            this.pageWrapper.css('padding-left', this.options.oldPadding)
        } else {
            this.menuDiv.css('margin-left', - this.options.newMargin);
            this.menuCollapseDiv.html('>');
            this.menuDiv.data('collapsed', 1);
            this.pageWrapper.css('padding-left', this.options.newPadding)
        }
    };

    return this.initialize(pageOptions);
};

$(function () {
    var sideMenu = new SideMenu(pageOptions);
});