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

$(function () {
    var branchPopUp = new BranchPopUp();
});