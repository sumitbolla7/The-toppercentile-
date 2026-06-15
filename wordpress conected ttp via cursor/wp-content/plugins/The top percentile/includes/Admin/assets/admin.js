(function () {
    "use strict";

    if (typeof TtpCrmAdmin === "undefined") {
        return;
    }

    var draggedCard = null;

    document.addEventListener("dragstart", function (event) {
        var card = event.target.closest(".ttp-pipeline-card");
        if (!card) {
            return;
        }
        draggedCard = card;
        card.classList.add("is-dragging");
    });

    document.addEventListener("dragend", function (event) {
        var card = event.target.closest(".ttp-pipeline-card");
        if (!card) {
            return;
        }
        card.classList.remove("is-dragging");
    });

    document.querySelectorAll(".ttp-pipeline-column").forEach(function (column) {
        column.addEventListener("dragover", function (event) {
            event.preventDefault();
            column.classList.add("is-drop-target");
        });

        column.addEventListener("dragleave", function () {
            column.classList.remove("is-drop-target");
        });

        column.addEventListener("drop", function (event) {
            event.preventDefault();
            column.classList.remove("is-drop-target");

            if (!draggedCard) {
                return;
            }

            var contactId = draggedCard.getAttribute("data-contact-id");
            var stage = column.getAttribute("data-stage");
            if (!contactId || !stage) {
                return;
            }

            var formData = new window.FormData();
            formData.append("action", "ttp_crm_update_stage");
            formData.append("nonce", TtpCrmAdmin.nonce);
            formData.append("contact_id", contactId);
            formData.append("stage", stage);

            window.fetch(TtpCrmAdmin.ajaxUrl, {
                method: "POST",
                credentials: "same-origin",
                body: formData,
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.success) {
                        window.alert("Could not update stage.");
                        return;
                    }
                    column.appendChild(draggedCard);
                })
                .catch(function () {
                    window.alert("Could not update stage.");
                });
        });
    });
})();
