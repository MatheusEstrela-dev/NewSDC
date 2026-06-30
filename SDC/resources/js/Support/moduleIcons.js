// Registry central de icones de identidade visual por modulo (SVG otimizado).
// Importa como URL (?url) para funcionar com ou sem vite-svg-loader.
import apartment from '../../images/modulos/apartment.svg?url';
import box from '../../images/modulos/box.svg?url';
import chart from '../../images/modulos/chart.svg?url';
import cistern from '../../images/modulos/cistern.svg?url';
import drop from '../../images/modulos/drop.svg?url';
import file from '../../images/modulos/file.svg?url';
import house from '../../images/modulos/house.svg?url';
import hydrant from '../../images/modulos/hydrant.svg?url';
import officeBuilding from '../../images/modulos/office-building.svg?url';
import shield from '../../images/modulos/shield.svg?url';
import tankTruck from '../../images/modulos/tank-truck.svg?url';
import heartAttack from '../../images/modulos/heart-attack.svg?url';
import helpDesk from '../../images/modulos/help-desk.svg?url';
import bookshelf from '../../images/modulos/bookshelf.svg?url';
import clock from '../../images/modulos/clock.svg?url';
import mountains from '../../images/modulos/mountains.svg?url';

// Catalogo bruto (todas as artes disponiveis).
export const ICONS = {
  apartment, box, chart, cistern, drop, file, house, hydrant,
  'office-building': officeBuilding, shield, 'tank-truck': tankTruck,
  'heart-attack': heartAttack, 'help-desk': helpDesk, bookshelf, clock, mountains,
};

// Mapa modulo -> icone (confirmado pelo usuario).
export const MODULE_ICONS = {
  orgaos: officeBuilding,
  estoque: box,
  decretacoes: chart,
  cisternas: cistern,
  pmda: drop,
  rat: file,
  inicio: house,
  dashboard: house,
  tdap: tankTruck,
  'plano-contingencia': shield,
  'ajuda-humanitaria': heartAttack,
  demandas: helpDesk,
  treinamento: bookshelf,
  plantao: clock,
  pae: mountains,
};

/** Retorna a URL do icone do modulo (ou null se nao mapeado). */
export function moduleIcon(modulo) {
  return MODULE_ICONS[modulo] ?? null;
}
