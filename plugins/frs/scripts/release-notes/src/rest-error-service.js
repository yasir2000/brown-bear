export default RestErrorService;

RestErrorService.$inject = [];

function RestErrorService() {
    const self = this;

    Object.assign(self, {
        getError,
        setError,
    });

    const error = {
        rest_error: "",
        rest_error_occured: false,
    };

    function getError() {
        return error;
    }

    function setError(rest_error) {
        error.rest_error_occured = true;
        error.rest_error = rest_error.code + " " + rest_error.message;
    }
}
