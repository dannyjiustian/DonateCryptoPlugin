document.addEventListener('DOMContentLoaded', function () {
    $(document).ready(function () {
        let currentActiveTab = { value: 0 };

        function getActiveTab() {
            var listItems = $("ul.ui-tabs-nav li");

            listItems.each(function (index, listItem) {
                var tabindexValue = $(listItem).attr("tabindex");
                if (tabindexValue >= 0) {
                    currentActiveTab.value = index;
                }
            });
        }

        // Watcher function
        function watchCurrentActiveTab(callback) {
            let currentValue = currentActiveTab.value;

            setInterval(() => {
                if (currentActiveTab.value !== currentValue) {
                    currentValue = currentActiveTab.value;
                    callback(currentValue);
                }
            }, 1000);
        }

        // Usage
        watchCurrentActiveTab(updatedValue => {
            if(updatedValue == 3)
            {
                console.log("Confirmation Page")
                let form = $("form#submitStep4Form");

                let paragraph = $("form#submitStep4Form p");

                let flagAgreement = $("<div>").text("Ini adalah flag agreement").attr('class', 'flag-agreement');

                paragraph.after(flagAgreement)
            }
        });

        setInterval(() => {
            getActiveTab();
        }, 0);
    });
});
