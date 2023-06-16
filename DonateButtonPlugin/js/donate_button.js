const abi_path =
  "/ojs/plugins/generic/DonateButtonPlugin/abi/contract_abi.json";
let contractAbi = [];

fetch(abi_path)
  .then((response) => response.json())
  .then((abiJson) => {
    contractAbi = abiJson;
  });

// Function to get the user's address
const getUserAddress = () => {
  return new Promise((resolve, reject) => {
    if (window.ethereum && window.ethereum.request) {
      window.ethereum
        .request({ method: "eth_requestAccounts" })
        .then(function (accounts) {
          resolve(accounts[0]);
        })
        .catch(function (error) {
          reject(error);
        });
    } else {
      reject(new Error("Ethereum provider not found"));
    }
  });
};

const sliceAddress = (address) => {
  return `${address.slice(0, 7)}....${address.slice(address.length - 6)}`;
};

// function create custom toast
const createToast = (type, title, message, color) => {
  let toastOptions = {
    title: title,
    message: message,
    position: "topRight",
    timeout: 3000,
    progressBarColor: color,
  };

  if (type === "info") {
    return iziToast.info(toastOptions);
  } else if (type === "success") {
    return iziToast.success(toastOptions);
  } else if (type === "error") {
    return iziToast.error(toastOptions);
  } else if (type === "loading") {
    return iziToast.info({
      id: "loading-toast",
      title: "Processing donation...",
      message: "Don't close this window !",
      position: "topRight",
      timeout: false,
      close: false,
      progressBar: false,
      overlay: true,
      zindex: 9999,
    });
  }
};

document.addEventListener("DOMContentLoaded", function () {
  const donateButton = document.getElementById("metamask-donate-button");
  const body = document.querySelector("body");
  donateButton.addEventListener("click", async function () {
    if (typeof window.ethereum !== "undefined") {
      const provider = new ethers.providers.Web3Provider(window.ethereum);
      const signer = provider.getSigner();

      //get targetContractAddress from db but now it's static not dynamic
      const targetContractAddress =
        "0x4D43B400eF65Cc48Ef68895b73239d6b981a56B3";

      try {
        const donateSnap = document.querySelector("#donate-snap");
        const closeDonateSnap = document.querySelector("#closeDonate");
        const addressWallet = document.querySelector("#address-crypto");
        const connectWallet = document.querySelector("#connectWallet");
        const donationAmount = document.querySelector("#amount");
        const donationMessage = document.querySelector("#message");
        const sendNow = document.querySelector("#sendNow");

        // reset card
        connectWallet.classList.remove("invisible");
        connectWallet.classList.add("visible");
        addressWallet.innerHTML = "Not Connect";
        sendNow.disabled = true;

        const donateProcess = async () => {
          if (donationAmount.value !== null && donationAmount.value !== "") {
            const amount = ethers.utils.parseEther(donationAmount.value);
            const balance = await provider.getBalance(address);
            const balanceInEth = ethers.utils.formatEther(balance);
            if (parseFloat(donationAmount) > parseFloat(balanceInEth)) {
              createToast(
                "error",
                "Insufficient balance",
                "Cannot proceed with the donation.",
                "#ff5f6d"
              );
              return;
            }
            createToast("loading");
            const overrides = {
              value: amount,
            };
            await contract.donate(overrides).then((e) => {
              console.log(e);
              iziToast.destroy();
              body.classList.remove("overflow-hidden");
              donateSnap.classList.add("invisible", "opacity-0");
              donateSnap.classList.remove("visible", "opacity-100");
              connectWallet.removeEventListener("click", checkWallet);
              sendNow.removeEventListener("click", donateProcess);
              donationAmount.value = "";
              donationMessage.value = "";
              createToast(
                "success",
                "Success",
                "Donation successful!",
                "#00b09b"
              );
            });
            return;
          } else {
            createToast(
              "error",
              "Enter the donation amount!",
              "Donation cannot be empty.",
              "#ff5f6d"
            );
            return;
          }
        };

        const process = async () => {
          const address = await getUserAddress();
          connectWallet.classList.remove("visible");
          connectWallet.classList.add("invisible");
          addressWallet.innerHTML = sliceAddress(address);
          sendNow.disabled = false;
  
          if (address !== null) {
            sendNow.addEventListener("click", donateProcess);
          }
        }

        const checkWallet = async () => {
          await window.ethereum
            .request({ method: "eth_requestAccounts" })
            .then((accountResBack) => {
              if (accountResBack.length > 0)
                connectWallet.removeEventListener("click", checkWallet);
                connectWallet.classList.remove("visible");
                connectWallet.classList.add("invisible");
                addressWallet.innerHTML = sliceAddress(accountResBack[0]);
                sendNow.disabled = false;
                process();
            });
        };

        // open Donate Snap
        body.classList.add("overflow-hidden");
        donateSnap.classList.remove("invisible", "opacity-0");
        donateSnap.classList.add("visible", "opacity-100");
        donationAmount.value = "";
        donationAmount.value = "";

        // close Donate Snap
        closeDonateSnap.addEventListener("click", function () {
          body.classList.remove("overflow-hidden");
          donateSnap.classList.add("invisible", "opacity-0");
          donateSnap.classList.remove("visible", "opacity-100");
          sendNow.disabled = true;
          donationAmount.value = "";
          donationMessage.value = "";
          connectWallet.removeEventListener("click", checkWallet);
          sendNow.removeEventListener("click", donateProcess);
        });

        const accounts = await provider.listAccounts();
        if (accounts.length === 0) {
          connectWallet.addEventListener("click", checkWallet);
        }

        const contract = new ethers.Contract(
          targetContractAddress,
          contractAbi,
          signer
        );

        if (addressWallet.innerHTML !== "Not Connect" || accounts.length > 0) {
          process();
        }
      } catch (error) {
        console.error("Error:", error);

        createToast("error", "Error", error.message, "#ff5f6d");
        return;
      }
    } else {
      createToast(
        "error",
        "Error",
        "MetaMask is not installed or not enabled",
        "#ff5f6d"
      );
      return;
    }
  });
});
