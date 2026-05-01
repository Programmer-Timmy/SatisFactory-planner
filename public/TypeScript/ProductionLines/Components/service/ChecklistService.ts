declare const window: any;

export async function saveChecklist(productionLineId: number, checklist: any[]): Promise<any> {
    const post = {
        productionLineId: productionLineId,
        checklist: JSON.stringify(checklist)
    };

    // find CSRF token meta if exists
    const metaCsrf = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (metaCsrf) {
        (post as any).csrf = metaCsrf.content;
    }

    return new Promise((resolve, reject) => {
        try {
            window.$.ajax({
                url: '/private/ajax/saveChecklist.php',
                method: 'POST',
                data: post,
                dataType: 'json',
                success: (data: any) => resolve(data),
                error: (xhr: any, status: any, err: any) => reject(err || status)
            });
        } catch (e) {
            reject(e);
        }
    });
}
