import angular from "angular";

import SharedPropertiesService from "./shared-properties-service.js";

export default angular
    .module("shared-properties", [])
    .service("SharedPropertiesService", SharedPropertiesService).name;
