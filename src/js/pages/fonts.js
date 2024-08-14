/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

(() => {
    // eslint-disable-next-line no-new, no-undef
    new simpleDatatables.DataTable("#fonts", {
        locale: "ca",
        paging: false,
        labels: {
            noResults: "No s'ha trobat cap resultat",
        },
        columns: [
            {
                select: 2,
                sort: "asc",
            },
        ],
        tableRender: (_data, table, type) => {
            if (type === "print") {
                return table;
            }
            const tHead = table.childNodes[0];
            const filterHeaders = {
                nodeName: "TR",
                childNodes: tHead.childNodes[0].childNodes.map((th, index) => {
                    const buttonNode = th.childNodes.find((node) => node.nodeName === "BUTTON");
                    const labelText =
                        buttonNode && buttonNode.childNodes.length > 0 && buttonNode.childNodes[0].nodeName === "#text"
                            ? buttonNode.childNodes[0].data
                            : index + 1;
                    return {
                        nodeName: "TH",
                        childNodes: [
                            {
                                nodeName: "INPUT",
                                attributes: {
                                    "class": "datatable-input",
                                    "type": "search",
                                    "data-columns": `[${index}]`,
                                    "data-and": true,
                                    "aria-label": `Cerca a la columna ${labelText}`,
                                },
                            },
                        ],
                    };
                }),
            };
            tHead.childNodes.push(filterHeaders);
            return table;
        },
    });
})();
