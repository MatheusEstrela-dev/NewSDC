import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import EmailShell from './Components/Emails/Templates/EmailShell.vue';
import EmailChangeVerificationBody from './Components/Emails/Organisms/EmailChangeVerificationBody.vue';
import EmailChangeNoticeBody from './Components/Emails/Organisms/EmailChangeNoticeBody.vue';

const COMPONENTS: Record<string, any> = {
    EmailChangeVerification: EmailChangeVerificationBody,
    EmailChangeNotice: EmailChangeNoticeBody,
};

const TITLES: Record<string, string> = {
    EmailChangeVerification: 'Codigo de verificacao',
    EmailChangeNotice: 'Pedido de troca de e-mail',
};

export async function renderEmail(
    componentName: string,
    props: Record<string, any>,
): Promise<string> {
    const Body = COMPONENTS[componentName];
    if (!Body) {
        throw new Error(`Unknown email component: ${componentName}`);
    }

    const app = createSSRApp({
        render: () =>
            h(
                EmailShell,
                { title: TITLES[componentName] },
                {
                    default: () => h(Body, props),
                },
            ),
    });

    return await renderToString(app);
}

// CLI mode: node email-ssr.js <componentName> <jsonProps>
// The PHP VueEmailRenderer service shells out to this entry.
if (process.argv[1]?.includes('email-ssr')) {
    const componentName = process.argv[2];
    const propsJson = process.argv[3] || '{}';

    if (!componentName) {
        process.stderr.write('Usage: node email-ssr.js <componentName> <jsonProps>\n');
        process.exit(2);
    }

    let props: Record<string, any>;
    try {
        props = JSON.parse(propsJson);
    } catch (e) {
        process.stderr.write('Invalid JSON props\n');
        process.exit(3);
    }

    renderEmail(componentName, props)
        .then((html) => process.stdout.write(html))
        .catch((err) => {
            process.stderr.write(`Render error: ${err.message}\n`);
            process.exit(1);
        });
}
