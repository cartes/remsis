import "virtual:uno.css";
import "./bootstrap";

import Alpine from "alpinejs";
import axios from "axios";

window.axios = axios;
window.Alpine = Alpine;

Alpine.start();
