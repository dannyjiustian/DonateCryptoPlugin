const abi_path = "/ojs-3.3.0-14/plugins/generic/DonateButtonPlugin/abi/contract_abi.json";
let contractAbi = [];

fetch(abi_path)
    .then(response => response.json())
    .then(abiJson => {
        contractAbi = abiJson;
    });

// Function to get the user's address
function getUserAddress() {
    return new Promise((resolve, reject) => {
        if (window.ethereum && window.ethereum.request) {
            window.ethereum.request({ method: 'eth_requestAccounts' })
                .then(function (accounts) {
                    resolve(accounts[0]);
                })
                .catch(function (error) {
                    reject(error);
                });
        } else {
            reject(new Error('Ethereum provider not found'));
        }
    });
}

function createToast(type, title, message, color) {
    let toastOptions = {
        title: title,
        message: message,
        position: 'topRight',
        timeout: 3000,
        progressBarColor: color,
    };

    if (type === 'info') {
        return iziToast.info(toastOptions)
    } else if (type === 'success') {
        return iziToast.success(toastOptions)
    } else if (type === 'error') {
        return iziToast.error(toastOptions)
    } else if (type === 'loading') {
        return iziToast.info({
            id: 'loading-toast',
            title: 'Processing donation...',
            message: 'Don\'t close this window !',
            position: 'topRight',
            timeout: false,
            close: false,
            progressBar: false,
            overlay: true,
            zindex: 9999
        })
    }

    return;
}


document.addEventListener('DOMContentLoaded', function () {
    const donateButton = document.getElementById('metamask-donate-button');
    donateButton.addEventListener('click', async function () {
        if (typeof window.ethereum !== 'undefined') {
            const provider = new ethers.providers.Web3Provider(window.ethereum);
            const signer = provider.getSigner();
            const targetContractAddress = '0x7e37355904356EfE4172cBd4df6cf0BF1f92C24E';

            try {
                const accounts = await provider.listAccounts();
                if (accounts.length === 0) {
                    createToast("error", 'Not Logged In', 'Please log in to your MetaMask account', "#ff5f6d")
                    await window.ethereum.request({ method: 'eth_requestAccounts' });
                    return;
                }

                const contract = new ethers.Contract(targetContractAddress, contractAbi, signer);

                const address = await getUserAddress();
                console.log('User Address:', address);

                if (address !== null) {
                    const donationAmount = prompt('Enter the donation amount in Ether:');

                    if (donationAmount !== null && donationAmount !== "") {
                        const amount = ethers.utils.parseEther(donationAmount);

                        const balance = await provider.getBalance(address);
                        const balanceInEth = ethers.utils.formatEther(balance);

                        if (parseFloat(donationAmount) > parseFloat(balanceInEth)) {
                            createToast("error", 'Insufficient balance', 'Cannot proceed with the donation.', "#ff5f6d")
                            return;
                        }
                        if (parseFloat(donationAmount) <= 0) {
                            createToast("error", 'Enter the donation !', 'Donation amount must be greater than 0.', "#ff5f6d")
                            return;
                        }

                        createToast("loading");

                        const overrides = {
                            value: amount
                        };

                        await contract.donate(overrides);

                        createToast("success", 'Success', 'Donation successful!', "#00b09b")
                        return;
                    } else {
                        if (donationAmount == "") {
                            createToast("error", 'Enter the donation !', 'Donation cannot be empty.', "#ff5f6d")
                            return;
                        }
                        createToast("info", 'Cancelled', 'Donation cancelled!', "#ff5f6d")
                        return;
                    }
                }
            } catch (error) {
                console.error('Error:', error);

                createToast("error", 'Error', error.message, "#ff5f6d")
                return;
            }
        } else {
            createToast("error", 'Error', 'MetaMask is not installed or not enabled', "#ff5f6d")
            return;
        }
    });
});
