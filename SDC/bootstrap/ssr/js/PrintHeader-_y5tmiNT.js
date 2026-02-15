import{ssrRenderTeleport as k,ssrRenderStyle as o,ssrRenderComponent as m,ssrInterpolate as r,ssrRenderSlot as $,ssrRenderAttrs as S,ssrRenderAttr as z}from"vue/server-renderer";import{ref as C,watch as R,onMounted as E,onUnmounted as I,useSSRContext as v,mergeProps as P}from"vue";import{_ as T}from"./XMarkIcon-DwM7WIfF.js";import{_ as b}from"./PrinterIcon-DPElmuP-.js";import{_ as j}from"./AuthenticatedLayout-Bke0-0JW.js";const f={__name:"BasePrintModal",__ssrInlineRender:!0,props:{show:{type:Boolean,default:!1},title:{type:String,default:"Imprimir Documento"},documentTitle:{type:String,default:"Documento"},loading:{type:Boolean,default:!1}},emits:["close"],setup(e,{expose:s,emit:t}){const l=e,c=t,p=C(null);R(()=>l.show,i=>{i?document.body.style.overflow="hidden":document.body.style.overflow=null},{immediate:!0});const h=()=>{c("close")},u=i=>{i.key==="Escape"&&l.show&&h()};E(()=>document.addEventListener("keydown",u)),I(()=>{document.removeEventListener("keydown",u),document.body.style.overflow=null});function w(){const i=p.value;if(!i)return;const d=window.open("","_blank");d&&(d.document.write(`
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>${l.documentTitle}</title>
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
      ${i.innerHTML}
    </body>
    </html>
  `),d.document.close(),d.focus(),setTimeout(()=>{d.print()},250))}return s({printContentRef:p,handlePrint:w}),(i,d,a,D)=>{k(d,n=>{n(`<div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0" style="${o([{"z-index":"9999 !important"},e.show?null:{display:"none"}])}" scroll-region data-v-2525fbdd><div class="fixed inset-0 transform transition-all" style="${o([{"z-index":"9998 !important"},e.show?null:{display:"none"}])}" data-v-2525fbdd><div class="absolute inset-0 bg-slate-900/70 backdrop-blur-md" data-v-2525fbdd></div></div><div class="relative mb-6 bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-2xl ring-1 ring-black/10 transform transition-all sm:w-full sm:mx-auto max-w-6xl isolate" style="${o([{"z-index":"10000 !important","backdrop-filter":"none !important","-webkit-backdrop-filter":"none !important"},e.show?null:{display:"none"}])}" data-v-2525fbdd><div class="flex items-center justify-between px-6 py-4 bg-sky-600 text-white" data-v-2525fbdd><h3 class="text-lg font-semibold flex items-center gap-2" data-v-2525fbdd>`),n(m(b,{class:"w-5 h-5"},null,a)),n(` ${r(e.title)}</h3><div class="flex items-center gap-3" data-v-2525fbdd><button type="button" class="px-4 py-2 bg-white text-sky-600 rounded-lg font-medium hover:bg-sky-50 transition-colors flex items-center gap-2" data-v-2525fbdd>`),n(m(b,{class:"w-4 h-4"},null,a)),n(' Imprimir </button><button type="button" class="p-2 hover:bg-sky-700 rounded-lg transition-colors" data-v-2525fbdd>'),n(m(T,{class:"w-5 h-5"},null,a)),n('</button></div></div><div class="max-h-[80vh] overflow-y-auto p-6 bg-gray-100 dark:bg-gray-900" data-v-2525fbdd>'),e.loading?n('<div class="flex items-center justify-center py-12" data-v-2525fbdd><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600" data-v-2525fbdd></div><span class="ml-3 text-gray-600 dark:text-gray-400" data-v-2525fbdd>Carregando dados...</span></div>'):(n('<div class="print-content bg-white" data-v-2525fbdd>'),$(i.$slots,"default",{},null,n,a),n("</div>")),n("</div></div></div>")},"body",!1,a)}}},x=f.setup;f.setup=(e,s)=>{const t=v();return(t.modules||(t.modules=new Set)).add("resources/js/Components/Organisms/Print/BasePrintModal.vue"),x?x(e,s):void 0};const N=j(f,[["__scopeId","data-v-2525fbdd"]]),y={__name:"PrintHeader",__ssrInlineRender:!0,props:{titulo:{type:String,default:"SISTEMA INTEGRADO DE DEFESA CIVIL"},subtitulo:{type:String,default:"DOCUMENTO"},numero:{type:String,default:null},labelNumero:{type:String,default:"DOC"},logoSrc:{type:String,default:"/imgs/logo_dc.png"}},setup(e){return(s,t,l,c)=>{t(`<div${S(P({class:"card-header bg-primary text-white p-3",style:{"background-color":"#003d82 !important"}},c))}><div class="flex items-center justify-between"><div style="${o({flex:"0 0 80px"})}"><img${z("src",e.logoSrc)} alt="Defesa Civil MG" class="brasao-logo" style="${o({"max-width":"70px",height:"auto"})}"></div><div class="flex-grow text-center px-3"><h5 class="mb-2 font-bold text-white" style="${o({"font-size":"1.1rem"})}">${r(e.titulo)}</h5><h4 class="mb-0 font-bold text-white" style="${o({"font-size":"1.3rem"})}">${r(e.subtitulo)}</h4></div><div class="text-right" style="${o({flex:"0 0 180px"})}">`),e.numero?t(`<div class="inline-block px-2 py-1" style="${o({"font-size":"0.75rem","background-color":"#f5f5f5",color:"#333"})}"><strong>${r(e.labelNumero)}:</strong> ${r(e.numero)}</div>`):t("<!---->"),t("</div></div></div>")}}},g=y.setup;y.setup=(e,s)=>{const t=v();return(t.modules||(t.modules=new Set)).add("resources/js/Components/Organisms/Print/Sections/PrintHeader.vue"),g?g(e,s):void 0};export{N as B,y as _};
