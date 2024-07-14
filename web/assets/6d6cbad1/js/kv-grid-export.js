/*!
 * @package   yii2-grid
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2019
 * @version   3.3.0
 *
 * Grid Export Validation Module for Yii's Gridview. Supports export of
 * grid data as CSV, HTML, or Excel.
 *
 * Author: Kartik Visweswaran
 * Copyright: 2014 - 2019, Kartik Visweswaran, Krajee.com
 * For more JQuery plugins visit http://plugins.krajee.com
 * For more Yii related demos visit http://demos.krajee.com
 */
(function ($) {
    "use strict";
    var $h, GridExport, URN = 'urn:schemas-microsoft-com:office:', XMLNS = 'http://www.w3.org/TR/REC-html40';
    // noinspection XmlUnusedNamespaceDeclaration
    $h = {
        replaceAll: function (str, from, to) {
            return str.split(from).join(to);
        },
        isEmpty: function (value, trim) {
            return value === null || value === undefined || value.length === 0 || (trim && $.trim(value) === '');
        },
        popupDialog: function (url, name, w, h) {
            var left = (screen.width / 2) - (w / 2), top = 60, existWin = window.open('', name, '', true);
            existWin.close();
            return window.open(url, name,
                'toolbar=no, location=no, directories=no, status=yes, menubar=no, scrollbars=no, ' +
                'resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
        },
        slug: function (strText) {
            return strText.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
        },
        templates: {
            html: '<!DOCTYPE html>' +
            '<meta http-equiv="Content-Type" content="text/html;charset={encoding}"/>' +
            '<meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1"/>' +
            '{css}' +
            '<style>.kv-wrap{padding:20px}</style>' +
            '<body class="kv-wrap">' +
            '{data}' +
            '</body>',
            pdf: '{before}\n{data}\n{after}',
            excel: '<html xmlns:o="' + URN + 'office" xmlns:x="' + URN + 'excel" xmlns="' + XMLNS + '">' +
            '<head>' +
            '<meta http-equiv="Content-Type" content="text/html;charset={encoding}"/>' +
            '{css}' +
            '<!--[if gte mso 9]>' +
            '<xml>' +
            '<x:ExcelWorkbook>' +
            '<x:ExcelWorksheets>' +
            '<x:ExcelWorksheet>' +
            '<x:Name>{worksheet}</x:Name>' +
            '<x:WorksheetOptions>' +
            '<x:DisplayGridlines/>' +
            '</x:WorksheetOptions>' +
            '</x:ExcelWorksheet>' +
            '</x:ExcelWorksheets>' +
            '</x:ExcelWorkbook>' +
            '</xml>' +
            '<![endif]-->' +
            '</head>' +
            '<body>' +
            '{data}' +
            '</body>' +
            '</html>',
            popup: '<html style="display:table;width:100%;height:100%;">' +
            '<title>Grid Export - &copy; Krajee</title>' +
            '<body style="display:table-cell;font-family:Helvetica,Arial,sans-serif;color:#888;font-weight:bold' +
            ';line-height:1.4em;text-align:center;vertical-align:middle;width:100%;height:100%;padding:0 10px;">' +
            '{msg}' +
            '</body>' +
            '</html>'
        }
    };
    GridExport = function (element, options) {
        //noinspection JSUnresolvedVariable
        var self = this, gridOpts = options.gridOpts, genOpts = options.genOpts;
        self.$element = $(element);
        //noinspection JSUnresolvedVariable
        self.gridId = gridOpts.gridId;
        self.$grid = $("#" + self.gridId);
        self.dialogLib = options.dialogLib;
        self.messages = gridOpts.messages;
        self.target = gridOpts.target;
        self.exportConversions = gridOpts.exportConversions;
        self.showConfirmAlert = gridOpts.showConfirmAlert;
        self.action = gridOpts.action;
        self.bom = gridOpts.bom;
        self.encoding = gridOpts.encoding;
        self.module = gridOpts.module;
        self.filename = genOpts.filename;
        self.expHash = genOpts.expHash;
        self.showHeader = genOpts.showHeader;
        self.showFooter = genOpts.showFooter;
        self.showPageSummary = genOpts.showPageSummary;
        self.$table = self.$grid.find('.kv-grid-table:first');
        self.columns = self.showHeader ? 'td,th' : 'td';
        self.alertMsg = options.alertMsg;
        self.config = options.config;
        self.popup = '';
        self.listen();
    };

    GridExport.prototype = {
        constructor: GridExport,
        getArray: function (expType) {
            var self = this, $table = self.clean(expType), head = [], data = {};
            if (self.config.colHeads !== undefined && self.config.colHeads.length > 0) {
                head = self.config.colHeads;
            } else {
                $table.find('thead tr th').each(function (i) {
                    var str = $(this).text().trim(), slugStr = $h.slug(str);
                    head[i] = (!self.config.$h.slugColHeads || $h.isEmpty(slugStr)) ? 'col_' + i : slugStr;
                });
            }
            $table.find('tbody tr:has("td")').each(function (i) {
                data[i] = {};
                //noinspection JSValidateTypes
                $(this).children('td').each(function (j) {
                    var col = head[j];
                    data[i][col] = $(this).text().trim();
                });
            });
            return data;
        },
        setPopupAlert: function (msg) {
            var self = this;
            if (self.popup.document === undefined) {
                return;
            }
            if (arguments.length && arguments[1]) {
                var el = self.popup.document.getElementsByTagName('body');
                setTimeout(function () {
                    el[0].innerHTML = msg;
                }, 1200);
            } else {
                var newmsg = $h.templates.popup.replace('{msg}', msg);
                self.popup.document.write(newmsg);
            }
        },
        processExport: function (callback, arg) {
            var self = this;
            setTimeout(function () {
                if (!$h.isEmpty(arg)) {
                    self[callback](arg);
                } else {
                    self[callback]();
                }
            }, 100);
        },
        listenClick: function (callback) {
            var self = this, arg = arguments.length > 1 ? arguments[1] : '', lib = window[self.dialogLib];
            self.$element.off("click.gridexport").on("click.gridexport", function (e) {
                e.stopPropagation();
                e.preventDefault();
                if (!self.showConfirmAlert) {
                    self.processExport(callback, arg);
                    return;
                }
                var msgs = self.messages, msg1 = $h.isEmpty(self.alertMsg) ? '' : self.alertMsg,
                    msg2 = $h.isEmpty(msgs.allowPopups) ? '' : msgs.allowPopups,
                    msg3 = $h.isEmpty(msgs.confirmDownload) ? '' : msgs.confirmDownload, msg = '';
                if (msg1.length && msg2.length) {
                    msg = msg1 + '\n\n' + msg2;
                } else {
                    if (!msg1.length && msg2.length) {
                        msg = msg2;
                    } else {
                        msg = (msg1.length && !msg2.length) ? msg1 : '';
                    }
                }
                if (msg3.length) {
                    msg = msg + '\n\n' + msg3;
                }
                if ($h.isEmpty(msg)) {
                    return;
                }
                lib.confirm(msg, function (result) {
                    if (result) {
                        self.processExport(callback, arg);
                    }
                    e.preventDefault();
                });
                return false;
            });
        },
        listen: function () {
            var self = this;
            if (self.$element.hasClass('export-csv')) {
                self.listenClick('exportTEXT', 'csv');
            }
            if (self.$element.hasClass('export-txt')) {
                self.listenClick('exportTEXT', 'txt');
            }
            if (self.$element.hasC