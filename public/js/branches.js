var BranchPopUp = function () {

    this.options = {
    };

    this.initialize = function () {
        this.bindElements();
    };

    this.bindElements = function () {
        var bindClass = $(".inlineError");
        var popUpDiv = $("#branchPopUp");

        popUpDiv.hide();

        bindClass.bind("mouseover", {popUpDiv: popUpDiv}, this.showPopup);
        bindClass.bind("mouseout", {popUpDiv: popUpDiv}, this.hidePopup);
    };

    this.showPopup = function (event) {
        var target = $(event.target);
        var popUp = event.data.popUpDiv;
        popUp.html(target.data('msg'));
        popUp.css({'top':event.pageY + 10,'left':event.pageX + 10}).show();
    };
    this.hidePopup = function (event) {
        event.data.popUpDiv.hide();
    };

    return this.initialize();
};

var BranchFileToggle = function () {

    this.initialize = function () {
        this.bindElements();
    };

    this.bindElements = function () {
        var bindClass = $(".header");

        bindClass.bind("click", this.toggleFile);
    };

    this.toggleFile = function (event) {
        var holder = $('#'+event.target.id+'Diff');

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
    var branchPopUp = new BranchPopUp();
    var branchFileToggle = new BranchFileToggle();
});