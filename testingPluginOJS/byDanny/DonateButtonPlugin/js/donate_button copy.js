const abi_path =
  "/ojs/plugins/generic/DonateButtonPlugin/abi/contract_abi.json";
let contractAbi;

const details = {
  network: "sepolia",
  url_api_key:
    "https://eth-sepolia.g.alchemy.com/v2/poW824z7baY51XHHw5_9oqfNZo7Mcnav",
  private_key_account:
    "dfa5e75b3dbcc8e3b928e5723dab7ce657c620cac73bd991dbd8268aaac55a18",
};

let formBody = [];
for (var property in details) {
  const encodedKey = encodeURIComponent(property);
  const encodedValue = encodeURIComponent(details[property]);
  formBody.push(encodedKey + "=" + encodedValue);
}
formBody = formBody.join("&");

fetch("http://localhost:3000/createSmartContract", {
  method: "post",
  headers: {
    "Content-Type": "application/x-www-form-urlencoded",
  },
  body: formBody,
})
  .then((rsp) => rsp.json())
  .then((obj) => {
    // console.log(obj.data.address_contract);
    // console.log(obj.data.abi_json_url);
    fetch(obj.data.abi_json_url, {
      method: "get",
      headers: {
        "Accept": "application/json",
      },
    })
      .then((res) => res.json())
      .then((obj) => {
        contractAbi = obj.abi
        console.log(contractAbi);
      })
      .catch((response) => {
        console.log(`error fetch abi_json = ${response}`);
      });
  })
  .catch((response) => {
    console.log(`error fetch new smart contract= ${response}`);
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
      const targetContractAddress = [
        {
          address: "0x4D43B400eF65Cc48Ef68895b73239d6b981a56B3",
        },
        {
          address: "0x4F308f137Bf030a016c4C903A119844b0E5B2F86",
        },
        {
          address: "0x7e37355904356EfE4172cBd4df6cf0BF1f92C24E",
        },
      ];

      const contract_1 = new ethers.Contract(
        targetContractAddress[0].address,
        contractAbi,
        signer
      );
      const contract_2 = new ethers.Contract(
        targetContractAddress[1].address,
        contractAbi,
        signer
      );
      const contract_3 = new ethers.Contract(
        targetContractAddress[2].address,
        contractAbi,
        signer
      );

      try {
        // get all element snap
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
            const amount = parseFloat(
              ethers.utils.parseEther(donationAmount.value)
            );
            const balance = await provider.getBalance(await getUserAddress());
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
            // split amount
            const amount_1 = amount * 0.3; // 30% for people 1
            const amount_2 = amount * 0.1; // 10% for people 2
            const amount_3 = amount * 0.6; // 60% for people 3

            createToast("loading");
            const overrides_1 = {
              value: amount_1,
            };
            const overrides_2 = {
              value: amount_2,
            };
            const overrides_3 = {
              value: amount_3,
            };
            await contract_1.donate(overrides_1).then(async () => {
              await contract_2.donate(overrides_2).then(async () => {
                await contract_3.donate(overrides_3).then(() => {
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
              });
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
        };

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
