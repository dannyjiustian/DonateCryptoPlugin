document.addEventListener('DOMContentLoaded', function () {
    $(document).ready(function () {
        let currentActiveTab = { value: 0 };
        let publications = [];
        const submissionId = getUrlParameter('submissionId');
        let agreed = false;
        const url = window.location.href;
        let authorWalletList = [];

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
            fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/publications.php?submissionId=' + submissionId)
                .then(response => response.json())
                .then(data => {
                    publications = data.data[0]
                    // console.log(publications);
                })
                .catch(error => {
                    console.log(error);
                });
        }

        function updateAgreement(agree, submissionId) {
            fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/publications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    submissionId: submissionId,
                    agreement: agree,
                })
            })
                .then(response => response.json())
                .then(data => {
                    // console.log(data);
                })
                .catch(error => {
                    console.log(error);
                });
        }

        function checkAuthorWallet() {
            fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/check_wallet.php?publication_id=' + submissionId)
                .then(response => response.json())
                .then(data => {
                    let author_data = data.data;
                    if (author_data.length > 0) {
                        author_data.forEach(author => {
                            authorWalletList.push(author.crypto_wallet_address);
                        });
                    }
                    // console.log(publications);
                })
                .catch(error => {
                    console.log(error);
                });
        }

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
        // -------------------------------------------------------------------

        //fetch publications by submission id
        getPublications(submissionId);

        //If value changes to 3 or enter Confirmation Page
        watchCurrentActiveTab(updatedValue => {
            if (updatedValue == 3) {
                // checkAuthorWallet();
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
                }).text('Enable monetization in articles');

                flagAgreement.append(checkbox, label);

                paragraph.after(flagAgreement)

                checkbox.on('click', function () {
                    showModal()
                    $(this).prop('checked', false)
                });

                function showModal() {
                    $.get(server + path + '/plugins/generic/DonateButtonPlugin/templates/modal.tpl', function (data) {
                        var modalContent = $(data);
                        Swal.fire({
                            html: modalContent,
                            allowOutsideClick: false,
                            width: '50%',
                            confirmButtonText: "Confirm",
                            confirmButtonColor: "#006798",
                            didOpen: function () {
                                var agreeCheckbox = document.getElementById('agree-checkbox');
                                agreeCheckbox.checked = agreed;
                                agreeCheckbox.addEventListener('change', function () {
                                    var agree = agreeCheckbox.checked;
                                    if (agree) {
                                        agreed = true;
                                    } else {
                                        agreed = false;
                                    }

                                });

                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // console.log(agreed)
                                if (agreed) {
                                    checkbox.prop('checked', true);
                                } else {
                                    checkbox.prop('checked', false);
                                }
                            }
                        })
                    })
                }

                if (publications) {
                    // console.log(publications.author_agreement);
                    if (publications.author_agreement == "1") {
                        checkbox.prop('checked', true);
                        if (checkbox.is(":checked")) {
                            agreed = true;
                        }
                    } else {
                        checkbox.prop('checked', false);
                        agreed = false;
                    }
                }

                function checkModalVisibility() {
                    if ($('body').hasClass('modal_is_visible')) {
                        let check = false

                        if (authorWalletList.includes(null) || authorWalletList.includes("")) {
                            check = false
                        } else {
                            check = true
                        }

                        let okButton = $("button.ok.pkpModalConfirmButton");

                        if (agreed) {
                            if (check) {
                                //Aktif
                                okButton.prop('disabled', false);
                            } else {
                                //Tidak Aktif
                                okButton.prop('disabled', true);
                                $(".message").after(
                                    '<div class="message dp-error">*There is incomplete author data. Check the data in Enter Metadata tab</div>'
                                )
                            }

                        }

                        okButton.click(() => {
                            updateAgreement(agreed, submissionId);
                        });
                    } else {
                        console.log('Modal is not appearing');
                    }
                }

                submitButton.click(() => {
                    checkAuthorWallet();

                    setTimeout(checkModalVisibility, 100);
                })
            }
        });

        setInterval(() => {
            getActiveTab();
        }, 500);
    });
});
