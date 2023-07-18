<div role="tabpanel" id="smartcontract" aria-labelledby="smart-contract-button" class="pkpTab" hidden="hidden">
    <div id="smartContractContainer" style="">
        <div class="title_section">
            <h3>Smart Contract</h3>
            <div>
                <button type="button" class="pkp_button" id="settings">Settings</button>
            </div>
        </div>

        <div class="input_section">
            <h3>Publisher - Wallet Address</h3>
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <input type="text" placeholder="Input wallet address" id="publisher_wallet"
                        class="publisher_wallet field text" />
                    <span class="sub_label" style="margin-left: 5px;"></span>
                </div>
                <button class="pkp_button" id="validate_address" class="validate">
                    Validate
                </button>
            </div>
        </div>

        <div>
            <h3>Reviewer</h3>
            <table style="width: 100%" id="reviewer_table">
                <colgroup>
                    <col class="grid-column" style="width: 7%" />
                    <col class="grid-column" style="width: 20%" />
                    <col class="grid-column" style="width: 33%" />
                    <col class="grid-column" style="width: 40%" />
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col" style="text-align: left">No</th>
                        <th scope="col" style="text-align: left">Reviewer</th>
                        <th scope="col" style="text-align: left">Email</th>
                        <th scope="col" style="text-align: left">Wallet Address</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>

        <div class="add_reviewer_section">
            <div>
                <a href="http://localhost/ojs/index.php/journal_test/$$$call$$$/grid/settings/user/user-grid/add-user"
                    id="component-grid-settings-user-usergrid-addUser-button"
                    class="pkp_controllers_linkAction pkp_linkaction_addUser pkp_linkaction_icon_add_user">
                    <button class="pkp_button">Add Reviewer</button>
                </a>
                <script>
                    $(function() {
                        $('#component-grid-settings-user-usergrid-addUser-button').pkpHandler(
                            '$.pkp.controllers.linkAction.LinkActionHandler', {
                                staticId: "component-grid-settings-user-usergrid-addUser-button",
                                actionRequest: "$.pkp.classes.linkAction.ModalRequest",
                                actionRequestOptions: {
                                    "title": "Add Reviewer",
                                    "titleIcon": "modal_add_user",
                                    "canClose": "0",
                                    "closeOnFormSuccessId": null,
                                    "closeCleanVueInstances": [],
                                    "closeButtonText": "Close Panel",
                                    "modalHandler": "$.pkp.controllers.modal.AjaxModalHandler",
                                    "url": "http:\/\/localhost\/ojs\/index.php\/journal_test\/$$$call$$$\/grid\/settings\/user\/user-grid\/add-user",
                                }
                            }
                        );
                    });
                </script>
            </div>
        </div>
    </div>
</div>