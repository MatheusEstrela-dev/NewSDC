import{ref as b,watch as v,onMounted as y,onUnmounted as w,useSSRContext as _,mergeProps as P}from"vue";import{X as z}from"./XMarkIcon-D46A5QZk.js";import{P as C}from"./PrinterIcon-DHen2V6D.js";import{ssrRenderTeleport as $,ssrRenderStyle as r,ssrRenderComponent as u,ssrInterpolate as p,ssrRenderSlot as R,ssrRenderAttrs as j,ssrRenderAttr as I}from"vue/server-renderer";import{_ as S}from"./_plugin-vue_export-helper-DlAUqK2U.js";const f={__name:"BasePrintModal",props:{show:{type:Boolean,default:!1},title:{type:String,default:"Imprimir Documento"},documentTitle:{type:String,default:"Documento"},loading:{type:Boolean,default:!1}},emits:["close"],setup(i,{expose:o,emit:e}){const a=i,t=e,s=b(null);v(()=>a.show,d=>{d?document.body.style.overflow="hidden":document.body.style.overflow=null},{immediate:!0});const m=()=>{t("close")},l=d=>{d.key==="Escape"&&a.show&&m()};y(()=>document.addEventListener("keydown",l)),w(()=>{document.removeEventListener("keydown",l),document.body.style.overflow=null});function n(){const d=s.value;if(!d)return;const c=window.open("","_blank");c&&(c.document.write(`
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>${a.documentTitle}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; border-radius: 0 !important; }
        body { font-family: Arial, sans-serif; font-size: 10px; background: white; }
        .container { width: 210mm; max-width: 100%; margin: 0 auto; padding: 10mm; }
        .card { border: 2px solid #000; }
        .card-header { background: #003d82; color: white; padding: 12px; border-bottom: 2px solid #000; }
        .card-header-content { display: flex; align-items: center; justify-content: space-between; }
        .brasao { width: 70px; height: auto; }
        .header-text { text-align: center; flex: 1; padding: 0 12px; }
        .header-text h5 { font-size: 11px; margin-bottom: 4px; }
        .header-text h4 { font-size: 13px; }
        .bos-badge { background: #f5f5f5; color: #333; padding: 4px 8px; font-size: 10px; }
        .section-title { background: #2c3e50; color: white; padding: 6px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; border: 1px solid #000; }
        .subsection-title { background: #d5d5d5; color: #000; padding: 4px 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; border: 1px solid #000; border-bottom: none; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
        .field-label { background: #e8e8e8; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .field-value { background: white; font-size: 10px; min-height: 18px; }
        .historico-text { min-height: 100px; padding: 8px; text-align: justify; white-space: pre-wrap; font-size: 9px; line-height: 1.3; }
        .signature-line { text-align: center; padding-top: 15px; }
        ul { margin: 0; padding-left: 20px; list-style-type: disc; }
        li { margin-bottom: 5px; }
        @media print {
          body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
          @page { size: A4; margin: 10mm; }
        }
      </style>
    </head>
    <body>
      ${d.innerHTML}
    </body>
    </html>
  `),c.document.close(),c.focus(),setTimeout(()=>{c.print()},250))}o({printContentRef:s,handlePrint:n});const g={props:a,emit:t,printContentRef:s,close:m,closeOnEscape:l,handlePrint:n,ref:b,onMounted:y,onUnmounted:w,watch:v,XMarkIcon:z,PrinterIcon:C};return Object.defineProperty(g,"__isScriptSetup",{enumerable:!1,value:!0}),g}};function M(i,o,e,a,t,s,m,l){$(o,n=>{n(`<div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0" style="${r([{"z-index":"9999 !important"},t.show?null:{display:"none"}])}" scroll-region data-v-5044283c><!-- Backdrop with blur - this blurs the background --><div class="fixed inset-0 transform transition-all" style="${r([{"z-index":"9998 !important"},t.show?null:{display:"none"}])}" data-v-5044283c><div class="absolute inset-0 bg-slate-900/70 backdrop-blur-md" data-v-5044283c></div></div><!-- Modal content - isolated from blur --><div class="relative mb-6 bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-2xl ring-1 ring-black/10 transform transition-all sm:w-full sm:mx-auto max-w-6xl isolate" style="${r([{"z-index":"10000 !important","backdrop-filter":"none !important","-webkit-backdrop-filter":"none !important"},t.show?null:{display:"none"}])}" data-v-5044283c><div class="flex items-center justify-between px-6 py-4 bg-sky-600 text-white" data-v-5044283c><h3 class="text-lg font-semibold flex items-center gap-2" data-v-5044283c>`),n(u(s.PrinterIcon,{class:"w-5 h-5"},null,e)),n(` ${p(t.title)}</h3><div class="flex items-center gap-3" data-v-5044283c><button type="button" class="px-4 py-2 bg-white text-sky-600 rounded-lg font-medium hover:bg-sky-50 transition-colors flex items-center gap-2" data-v-5044283c>`),n(u(s.PrinterIcon,{class:"w-4 h-4"},null,e)),n(' Imprimir </button><button type="button" class="p-2 hover:bg-sky-700 rounded-lg transition-colors" data-v-5044283c>'),n(u(s.XMarkIcon,{class:"w-5 h-5"},null,e)),n('</button></div></div><div class="max-h-[80vh] overflow-y-auto p-6 bg-gray-100 dark:bg-gray-900" data-v-5044283c>'),t.loading?n('<div class="flex items-center justify-center py-12" data-v-5044283c><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600" data-v-5044283c></div><span class="ml-3 text-gray-600 dark:text-gray-400" data-v-5044283c>Carregando dados...</span></div>'):(n('<div class="print-content bg-white" data-v-5044283c>'),R(i.$slots,"default",{},null,n,e),n("</div>")),n("</div></div></div>")},"body",!1,e)}const h=f.setup;f.setup=(i,o)=>{const e=_();return(e.modules||(e.modules=new Set)).add("resources/js/Components/Organisms/Print/BasePrintModal.vue"),h?h(i,o):void 0};const H=S(f,[["ssrRender",M],["__scopeId","data-v-5044283c"],["__file","/var/www/resources/js/Components/Organisms/Print/BasePrintModal.vue"]]),x={__name:"PrintHeader",props:{titulo:{type:String,default:"SISTEMA INTEGRADO DE DEFESA CIVIL"},subtitulo:{type:String,default:"DOCUMENTO"},numero:{type:String,default:null},labelNumero:{type:String,default:"DOC"},logoSrc:{type:String,default:"/imgs/logo_dc.png"}},setup(i,{expose:o}){o();const e={};return Object.defineProperty(e,"__isScriptSetup",{enumerable:!1,value:!0}),e}};function O(i,o,e,a,t,s,m,l){o(`<div${j(P({class:"card-header bg-primary text-white p-3",style:{"background-color":"#003d82 !important"}},a))}><div class="flex items-center justify-between"><div style="${r({flex:"0 0 80px"})}"><img${I("src",t.logoSrc)} alt="Defesa Civil MG" class="brasao-logo" style="${r({"max-width":"70px",height:"auto"})}"></div><div class="flex-grow text-center px-3"><h5 class="mb-2 font-bold text-white" style="${r({"font-size":"1.1rem"})}">${p(t.titulo)}</h5><h4 class="mb-0 font-bold text-white" style="${r({"font-size":"1.3rem"})}">${p(t.subtitulo)}</h4></div><div class="text-right" style="${r({flex:"0 0 180px"})}">`),t.numero?o(`<div class="inline-block px-2 py-1" style="${r({"font-size":"0.75rem","background-color":"#f5f5f5",color:"#333"})}"><strong>${p(t.labelNumero)}:</strong> ${p(t.numero)}</div>`):o("<!---->"),o("</div></div></div>")}const k=x.setup;x.setup=(i,o)=>{const e=_();return(e.modules||(e.modules=new Set)).add("resources/js/Components/Organisms/Print/Sections/PrintHeader.vue"),k?k(i,o):void 0};const L=S(x,[["ssrRender",O],["__file","/var/www/resources/js/Components/Organisms/Print/Sections/PrintHeader.vue"]]);export{H as B,L as P};
