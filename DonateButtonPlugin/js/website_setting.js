document.addEventListener('DOMContentLoaded', function () {
    $(document).ready(function () {
        let currentActiveTab = "";
        const url = window.location.href;


        // ------------------------ FUNCTIONS ---------------------------
        function getServerFromUrl(url) {
            var parser = document.createElement('a');
            parser.href = url;
            var protocol = parser.protocol;
            var server = parser.hostname;
            var result = protocol + '//' + server;
            return result;
        }

        function getUrlBeforeIndexPhp(url) {
            var regex = /^(?:https?:\/\/[^/]+)?(.*?)(?=\/?index\.php)/;
            var matches = url.match(regex);
            if (matches && matches.length > 1) {
                return matches[1];
            }
            return url;
        }

        var server = getServerFromUrl(url);
        var path = getUrlBeforeIndexPhp(url);

        // -------------------------------------------------------------------

        function getFragment() {
            var urlFragment = window.location.hash;
            var setupFragment = urlFragment.split('/')[0];
            // console.log(setupFragment)
            currentActiveTab = setupFragment;
        }

        function watchCurrentFragment(callback) {
            let currentValue = currentActiveTab;

            setInterval(() => {
                if (currentActiveTab !== currentValue) {
                    currentValue = currentActiveTab;
                    callback(currentValue);
                }
            }, 0);
        }

        function defaultSetting() {
            //make information button select by default
            $("#information-button").addClass('pkpTab--isActive');
            $("#information-button").attr('aria-selected', true);

            //show information panel
            $("#information").addClass('pkpTab--isActive');
            $("#information").removeAttr('hidden');

            //hide smartcontract panel
            hideSmartContract();
        }

        function showSmartContract() {
            //show smartcontract panel
            $("#smartcontract").addClass('pkpTab--isActive');
            $("#smartcontract").removeAttr('hidden');
        }

        function hideSmartContract() {
            //hide smartcontract panel
            $("#smartcontract").removeClass('pkpTab--isActive');
            $("#smartcontract").attr('hidden', 'hidden');
        }

        watchCurrentFragment(value => {
            if (value == "#setup") {
                defaultSetting();

                $.get(server + path + '/plugins/generic/DonateButtonPlugin/templates/smart_contract_settings.tpl', function (data) {
                    var content = $(data);

                    $("#dateTime").after(content)
                })

                var tabListContainer = $('#setup .pkpTabs--side .pkpTabs__buttons');

                //check if button is exist or not
                if ($('#smart-contract-button').length === 0) {
                    let smartContractButton = `
                        <button aria-controls="smartContract" id="smart-contract-button" role="tab" tabindex="-1" class="pkpTabs__button">
                            Smart Contract
                        </button>`;

                    // append button if the button is not exist
                    tabListContainer.append(smartContractButton);
                }

                var buttons = tabListContainer.find('.pkpTabs__button');
                buttons.each(function () {
                    $(this).on('click', function () {
                        if ($(this).attr('id') == "smart-contract-button") {

                            //make this button selected
                            $(this).attr('aria-selected', 'true');

                            //make all button not selected
                            tabListContainer.find('.pkpTabs__button').not(this).removeAttr('aria-selected');

                            //find all div with role tabpanel and make it hidden
                            var tabPanelDiv = $('#setup .pkpTabs--side div[role="tabpanel"]');
                            tabPanelDiv.removeClass('pkpTab--isActive');
                            tabPanelDiv.attr('hidden', 'hidden');

                            //update url to /smartContract
                            var currentUrl = window.location.href;
                            var newUrl = currentUrl.replace(/#setup\/.*/, '#setup/smartContract');
                            history.pushState(null, '', newUrl);

                            //show the div with id #smartcontract
                            showSmartContract();
                            $("table#reviewer_table tbody").empty();
                            for (let i = 0; i < 5; i++) {
                                let tr = `
                                <tr class="gridRow">
                                <td class="first_column">
                                    <span class="gridCellContainer">
                                        ${i + 1}
                                    </span>
                                </td>
                                <td>
                                    <span>
                                        Justin
                                    </span>
                                </td>
                                <td>
                                    <span>
                                        justin.laurenso166@gmail.com
                                    </span>
                                </td>
                                <td>
                                    <span class="wallet-text">
                                        0x102B1d7D03bC57527c49A61DBFc5721F51c7dB59
                                    </span>
                                </td>
                            </tr>
                                `

                                $("table#reviewer_table tbody").append(tr)
                            }

                        } else {
                            //make smart contract button not selected
                            $("button#smart-contract-button").removeAttr('aria-selected');

                            //find a panel using button id and show the panel
                            var ariaLabelledByValue = $(this).attr('id');
                            var element = $("div[aria-labelledby='" + ariaLabelledByValue + "']");
                            element.addClass('pkpTab--isActive')
                            element.removeAttr('hidden')

                            //hide the div with id #smartcontract
                            hideSmartContract();
                        }
                    });
                })

                $(document).on('click', "#settings", ()=>{
                    $.get(server + path + '/plugins/generic/DonateButtonPlugin/templates/settings.tpl', function (data) {
                        var modalContent = $(data);
                        Swal.fire({
                            width: "1000",
                            html: modalContent,
                            allowOutsideClick: false,
                            showConfirmButton: true,
                            confirmButtonText: "Submit",
                            allowEscapeKey: false,
                            showCancelButton: true,
                            cancelButtonText: "Cancel",
                        })
                    })
                })

                $(document).on('click', "#add_reviewer", ()=>{
                    console.log("Add reviewer clicked")
                })
            } else {
                $("#smart-contract-button").remove();
            }
        })

        setInterval(() => {
            getFragment();
        }, 100)
    })

}); // end on click
