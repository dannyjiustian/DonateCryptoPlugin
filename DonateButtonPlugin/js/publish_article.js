document.addEventListener('DOMContentLoaded', function () {
    $(document).ready(function () {
        //-------------------------------------------VARIABLES  --------------------------------
        let currentActiveTab,
            submission_id,
            url = window.location.href,
            publisher_id,
            server = getServerFromUrl(url),
            path = getUrlBeforeIndexPhp(url),
            publications = [];


        //-------------------------------------------FUNCTIONS --------------------------------
        // Function to create a toast notification
        const createToast = (type, title, message, color) => {
            const toastOptions = {
                title: title,
                message: message,
                position: "topRight",
                timeout: 3000,
                progressBarColor: color,
            };

            switch (type) {
                case "info":
                    return iziToast.info(toastOptions);
                case "success":
                    return iziToast.success(toastOptions);
                case "error":
                    return iziToast.error(toastOptions);
                case "loading":
                    return iziToast.info({
                        id: "loading-toast",
                        title: "Creating Smart Contract...",
                        message: "Don't close this window!",
                        position: "topRight",
                        timeout: false,
                        close: false,
                        progressBar: false,
                        overlay: true,
                        zindex: 9999,
                    });
                default:
                    return;
            }
        };

        /**
         * Function to get a fragment from the URL
         * Example : #publication
         */
        function getFragment() {
            var urlFragment = window.location.hash;
            var setupFragment = urlFragment.split('/')[0];
            currentActiveTab = setupFragment;
        }

        /**
         * Function to watch current active tab
         * @param {*} callback 
         */
        function watchCurrentFragment(callback) {
            let currentValue = currentActiveTab;

            setInterval(() => {
                if (currentActiveTab !== currentValue) {
                    currentValue = currentActiveTab;
                    callback(currentValue);
                }
            }, 0);
        }

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


        /**
         * Function to create a smart contract when publish button clicked
         */
        async function createSmartContract() {
            // Show a loading toast notification
            createToast("loading");

            try {
                await fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/processGetData.php?type=createSmartContract&id_submission=' + submission_id + "&publisher_id=" + publisher_id)
                    .then((response) => response.json())
                    .then((data) => {
                        console.log(data);
                        if (data.status) {
                            iziToast.destroy();
                            createToast("success", "Success", "Smart Contract created", "#00b09b");
                        } else if (!data.status) {
                            iziToast.destroy();
                            createToast("error", "Error", data.message, "#ff5f6d");
                        }
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            } catch (error) {
                console.log(error)
            }
        }

        /**
        * function to get publication data by submission id
        * @param {*} submissionId 
        */
        async function getPublications() {
            try {
                await fetch(server + path + '/plugins/generic/DonateButtonPlugin/request/publications.php?submissionId=' + submission_id)
                    .then(response => response.json())
                    .then(data => {
                        publications = data.data[0]
                        console.log(publications)
                    })
                    .catch(error => {
                        console.log(error);
                    });
            } catch (error) {
                console.log(error)
            }
        }

        /**
         * Function to get publisher id by username
         * @param {*} username 
         */
        async function getPublisherId(username) {
            try {
                await fetch(server + path + "/plugins/generic/DonateButtonPlugin/request/users.php?type=getUserAddress&username=" + username)
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.data.length > 0) {
                            publisher_id = data.data[0].user_id;
                        }
                    })
                    .catch((error) => {
                        console.log(error)
                    })
            } catch (error) {
                createToast("error", "Error", error.message, "#ff5f6d");
            }
        }

        // Call the function to watch current fragment
        watchCurrentFragment(async value => {
            if (value === "#publication") {
                let url = window.location.href;
                const pattern = /\/workflow\/index\/(\d+)\/(\d+)/;

                const matches = url.match(pattern);
                if (matches) {
                    //Get username from this
                    setTimeout(async () => {
                        var span = $(".-screenReader").eq(1);
                        var text = span.text();
                        username = text;
                        if (username != "") {
                            await getPublisherId(username);
                        }
                        submission_id = matches[1];
                        console.log(submission_id)
                        await getPublications();
                    }, 500)
                }

                // Check publish function is available at DOM or not
                function checkPublishButton() {
                    let publishButton = $("button.pkpButton:contains('Publish')");

                    if (publishButton.length) {
                        // IF available then add a click to that button to create smart contract
                        publishButton.on('click', async function () {
                            // console.log("Publish")
                            if(publications.author_agreement == 1 && publications.publisher_agreement == 1 && publications.reviewer_agreement == 1){
                                await createSmartContract();
                            }
                        })
                    } else {
                        setTimeout(checkPublishButton, 100)
                    }
                }

                checkPublishButton();
            }
        })

        setInterval(() => {
            getFragment();
        }, 100)
    })
})