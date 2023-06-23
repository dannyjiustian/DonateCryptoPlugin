document.addEventListener('DOMContentLoaded', function () {
    $(document).ready(function () {
        let currentActiveTab = { value: 0 };
        let publications = [];
        const submissionId = getUrlParameter('submissionId');


        // ------------------------ FUNCTIONS ---------------------------
        function getActiveTab() {
            var listItems = $("ul.ui-tabs-nav li");

            listItems.each(function (index, listItem) {
                var tabindexValue = $(listItem).attr("tabindex");
                if (tabindexValue >= 0) {
                    currentActiveTab.value = index;
                }
            });
        }

        function getPublications(submissionId) {
            fetch('/ojs/plugins/generic/DonateButtonPlugin/request/publications.php?submissionId=' + submissionId)
                .then(response => response.json())
                .then(data => {
                    publications = data.data[0]
                    console.log(publications);
                })
                .catch(error => {
                    console.log(error);
                });
        }

        function postDataExample(agree, wallet, submissionId) {
            fetch('/ojs/plugins/generic/DonateButtonPlugin/request/publications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    submissionId: submissionId,
                    agreement: agree,
                    wallet_address: wallet,
                })
            })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                })
                .catch(error => {
                    console.log(error);
                });
        }

        async function getMetamaskAddress() {
            if (typeof window.ethereum !== 'undefined') {
                try {
                    // Request access to the user's MetaMask accounts
                    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });

                    if (accounts.length > 0) {
                        return accounts[0];
                    } else {
                        throw new Error('No accounts found in MetaMask.');
                    }
                } catch (error) {
                    throw new Error('Error requesting MetaMask accounts: ' + error.message);
                }
            } else {
                throw new Error('MetaMask not detected. Please make sure MetaMask is installed and connected.');
            }
        }



        // -------------------------------------------------------------------

        //Watch value changes
        function watchCurrentActiveTab(callback) {
            let currentValue = currentActiveTab.value;

            setInterval(() => {
                if (currentActiveTab.value !== currentValue) {
                    currentValue = currentActiveTab.value;
                    callback(currentValue);
                }
            }, 1000);
        }

        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        getPublications(submissionId);

        //If value changes to 3 or enter Confirmation Page
        watchCurrentActiveTab(updatedValue => {
            if (updatedValue == 3) {
                let agree = false;
                //Get submissionId from URL parameter
                console.log("Confirmation Page")
                // let form = $("form#submitStep4Form");
                let paragraph = $("form#submitStep4Form p");
                let submitButton = $("button.pkp_button.submitFormButton");

                let flagAgreement = $('<div>').addClass('flag-agreement');
                let checkbox = $('<input>').attr({
                    type: 'checkbox',
                    id: 'flagAgreementCheckbox',
                    class: 'flag-agreement-checkbox'
                });
                let label = $('<span>').attr({
                    'for': 'flagAgreementCheckbox', 'class': 'dp-span'
                }).text('Do you want this article to be able to accept donations later?');

                flagAgreement.append(checkbox, label);

                // wallet address container
                let walletAddress = $("<div>").attr({
                    "id": "wallet-address", "class": "dp-wallet-address hidden"
                });

                //label for wallet address
                let walletAddressLabel = $("<label>").attr({
                    'for': 'walletAddress', 'class': 'dp-label'
                }).text('Wallet Address');

                //input type for wallet address
                let walletAddressInput = $('<input>').attr({
                    type: 'text',
                    id: 'walletAddress',
                    class: 'wallet-address-input',
                    'required': true
                });

                //example of the wallet address
                let example = $("<div class='example'>Example : 0x12345678910123456ABCDEF</div>")

                //wallet button
                let walletButton = $('<button>').attr({
                    "id": "wallet-button",
                    "type": "button",
                    "class":"pkp_button"
                }).text("Get Wallet Address");

                walletAddress.append(walletAddressLabel, walletAddressInput, example, walletButton);

                walletButton.click(function () {
                    getMetamaskAddress()
                        .then((address) => {
                            walletAddressInput.val(address);
                        })
                        .catch((error) => {
                            console.error('Error:', error.message);
                        });

                })


                paragraph.after(flagAgreement, walletAddress)

                checkbox.on('change', function () {
                    if ($(this).is(':checked')) {
                        walletAddress.removeClass('hidden');
                        agree = true;
                    } else {
                        walletAddress.addClass('hidden');
                        agree = false;
                    }
                });

                if (publications) {
                    console.log(publications.author_agreement);
                    if (publications.author_agreement == "1") {
                        checkbox.prop('checked', true);
                        if (checkbox.is(":checked")) {
                            walletAddress.removeClass('hidden');
                            agree = true;
                            walletAddressInput.val(publications.wallet_address_author);
                        }
                    } else {
                        checkbox.prop('checked', false);
                        walletAddress.addClass('hidden');
                        agree = false;
                    }
                }

                function checkModalVisibility() {
                    if ($('body').hasClass('modal_is_visible')) {
                        let okButton = $("button.ok.pkpModalConfirmButton");
                        okButton.click(() => {
                            let walletValue = walletAddressInput.val();

                            if (walletValue !== "") {
                                postDataExample(agree, walletValue, submissionId)
                            }
                        });

                    } else {
                        console.log('Modal is not appearing');
                    }
                }

                submitButton.click(() => {
                    setTimeout(checkModalVisibility, 100);
                })
            }
        });

        setInterval(() => {
            getActiveTab();
        }, 500);
    });
});
