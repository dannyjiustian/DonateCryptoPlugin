document.addEventListener('DOMContentLoaded', function () {
    $(document).ready(function () {
        let currentActiveTab = { value: 0 };
        let modal_visible = false;
        let interval;
        let author = {
            author_id: null,
            email: null,
            publication_id: getUrlParameter('submissionId'),
            crypto_wallet_address: null,
        };
        let pkpGridInterval;
        let isCheckPKPCalled = false;
        const url = window.location.href;

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
                    // console.log(index)
                }
            });
        }

        function watchCurrentActiveTab(callback) {
            let currentValue = currentActiveTab.value;

            setInterval(() => {
                if (currentActiveTab.value !== currentValue) {
                    currentValue = currentActiveTab.value;
                    callback(currentValue);
                }
            }, 1000);
        }

        function getAuthorWallet() {
            fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/authors.php?author_id=' + author.author_id)
                .then(response => response.json())
                .then(data => {
                    let authors = data.data[0]
                    author.crypto_wallet_address = authors.crypto_wallet_address;
                })
                .catch(error => {
                    console.log(error);
                });
        }

        function editAuthorWallet() {
            fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/authors.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(author)
            })
                .then(response => response.json())
                .then(data => {
                    // console.log(data);
                    if (data.success) {
                        isCheckPKPCalled = false;
                    }
                })
                .catch(error => {
                    // console.log(error);
                });
        }

        function checkPKPGrid() {
            console.log("Checking")

            isCheckPKPCalled = true;
            if (pkpGridInterval == true) {
                let editButtons = $('a').filter(function () {
                    return /^component-grid-users-author-authorgrid-row-\d+-editAuthor-button-\w+$/.test(this.id);
                });

                editButtons.each(function (index, editButton) {
                    if (!editButton.hasEventListener) {
                        editButton.addEventListener('click', function () {
                            console.log(this.id);
                            author.author_id = this.id.match(/row-(\d+)/)[1];
                            if (author.author_id !== null) {
                                getAuthorWallet();
                            }
                            setTimeout(checkModalVisibility, 300);
                        });
                        editButton.hasEventListener = true;
                    }
                })

                let addButton = $('.pkp_controllers_linkAction.pkp_linkaction_addAuthor.pkp_linkaction_icon_add_user')

                addButton.click(() => {
                    author.author_id = null;
                    author.crypto_wallet_address = null;
                    setTimeout(checkModalVisibility, 300);
                })
            }
        }

        function checkModalVisibility() {
            if (modal_visible) {
                setTimeout(() => {
                    let target = $("#userGroupId")
                    let div = $("<div>").attr("class", "dp-wallet-address");
                    let walletAddressLabel = $("<label>").attr({
                        'for': 'walletAddress', 'class': 'dp-label'
                    }).text('Wallet Address');
                    let walletAddressInput = $('<input>').attr({
                        type: 'text',
                        id: 'walletAddress',
                        class: 'wallet-address-input',
                        'required': true
                    });

                    walletAddressInput.on('keydown', function (event) {
                        if (event.key === ' ') {
                            event.preventDefault();
                        }
                    });

                    if (!target.next('.dp-wallet-address').length) {
                        target.after(div.append(walletAddressLabel, walletAddressInput));
                    }

                    walletAddressInput.val(author.crypto_wallet_address);

                    walletAddressInput.on('input', function () {
                        author.crypto_wallet_address = $(this).val()
                    });


                    let saveButton = $(".pkp_button.submitFormButton");
                    if (!saveButton.hasEventListener) {
                        saveButton.click(function () {
                            // author.crypto_wallet_address = walletAddressInput.val();
                            author.email = $(".field.text.required.email").val();
                            if (author.crypto_wallet_address !== "" && author.crypto_wallet_address !== null) {
                                setTimeout(() => {
                                    editAuthorWallet();
                                }, 1000)
                            }
                        })
                        saveButton.hasEventListener = true;
                    }
                }, 500)
            } else {
                console.log('Modal is not appearing');
            }
        }

        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        watchCurrentActiveTab(updatedValue => {
            if (updatedValue == 2) {
                isCheckPKPCalled = false;

                interval = setInterval(() => {
                    if ($('body').hasClass('modal_is_visible')) {
                        modal_visible = true
                        // console.log('Modal is appearing')
                    } else {
                        modal_visible = false;
                    }
                }, 100)

                pkpGridInterval = setInterval(() => {
                    if ($('div').hasClass('pkp_controllers_grid')) {
                        pkpGridInterval = true
                        if (!isCheckPKPCalled) {
                            checkPKPGrid();
                        }
                        // console.log('pkp_controllers_grid')
                    } else {
                        pkpGridInterval = false
                        // console.log('dont have pkp_controllers_grid')


                    }
                }, 100)
            } else {
                clearInterval(interval)
                clearInterval(pkpGridInterval)
            }
        })

        setInterval(() => {
            getActiveTab();
        }, 500);

    })
})