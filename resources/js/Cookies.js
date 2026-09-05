import axios from "axios";

class LaravelCookieConsent {
    config;

    constructor(config) {
        this.config = config;
    }

    acceptAll() {
        return this.request(this.config['accept.all'])
            .then((response) => this.addScripts(response.data));
    }

    acceptEssentials() {
        return this.request(this.config['accept.essentials'])
            .then((response) => this.addScripts(response.data));
    }

    configure(data) {
        return this.request(this.config['accept.configuration'], data)
            .then((response) => this.addScripts(response.data));
    }

    reset() {
        return this.request(this.config['reset'])
            .then((response) => this.addNotice(response.data));
    }

    request(url, data = null) {
        return axios.post(url, data);
    }

    async addScripts(data) {
        if (!data.scripts) {
            return;
        }

        for (const script of data.scripts) {
            const temporaryElement = document.createElement("div");
            temporaryElement.innerHTML = script;

            const source = temporaryElement.querySelector("script");
            const tag = document.createElement("script");

            tag.textContent = source.textContent;

            for (const attribute of source.attributes) {
                tag.setAttribute(attribute.name, attribute.value);
            }

            tag.setAttribute("data-cookie-consent", "true");

            try {
                await new Promise((resolve, reject) => {
                    if (tag.src) {
                        tag.addEventListener("load", resolve, { once: true });
                        tag.addEventListener(
                            "error",
                            () => reject(new Error(`Failed to load script: ${tag.src}`)),
                            { once: true }
                        );
                    }

                    document.head.appendChild(tag);

                    if (!tag.src) {
                        resolve();
                    }
                });
            } catch (error) {
                console.error(error.message);
                throw error;
            }
        }
    }

    addNotice(data) {
        if (!data.notice) {
            return;
        }

        let tmp = document.createElement('div');
        tmp.innerHTML = data.notice;

        let cookies = tmp.querySelector('#cookies-policy')
        document.body.appendChild(cookies);

        let tags = tmp.querySelectorAll('[data-cookie-consent]');

        if (!tags.length) {
            return;
        }

        tags.forEach(tag => {
            if (tag.nodeName === 'SCRIPT') {
                const script = document.createElement('script');
                script.textContent = tag.textContent;
                document.body.appendChild(script);
            } else {
                document.body.appendChild(tag);
            }
        });
    }
}

window.addEventListener('DOMContentLoaded', () => {
    window.LaravelCookieConsent = new LaravelCookieConsent({ config: 1 });
});