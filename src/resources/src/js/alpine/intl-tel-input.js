import intlTelInput from "intl-tel-input";
import "intl-tel-input/styles";

window.intlTelInput = intlTelInput;

Alpine.data('intlTelInput', (model_code, model_number) => ({
    code: model_code,
    number: model_number,
    init() {
        const input = this.$el;

        const iti = window.intlTelInput(input, {
            initialCountry: "auto",
            separateDialCode: true,
            strictMode: true,
            geoIpLookup: (success, failure) => {
                fetch("https://ipapi.co/json").then(res => res.json()).then(data => success(data.country_code)).catch(() => failure());
            },
            loadUtils: () => import("intl-tel-input/utils"),
            customPlaceholder: (selectedCountryPlaceholder, selectedCountryData) => {
                return selectedCountryPlaceholder.replace(/[0-9]/g, "_");
            },
        });

        if (this.code && this.number) {
            const full = '+' + this.code + this.number;
            iti.setNumber(full);
        }

        input.addEventListener("input", (e) => {
            const countryData = iti.getSelectedCountryData();
            const fullNumber = iti.getNumber();

            if (!fullNumber) return;

            const dialCode = countryData.dialCode;
            const number = fullNumber.replace('+' + dialCode, '');

            this.code = dialCode;
            this.number = number;
        });
    }
}));
