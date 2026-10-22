// Client mirror of App\Workflows\GraphValidator, for instant feedback. The server re-checks on publish.
import { TYPES } from './nodes';

const TERMINAL = ['archive'];

export function validateGraph(g) {
  const errs = [];
  const add = (node, message) => errs.push({ node, message });
  const nodes = Object.fromEntries(g.nodes.map((n) => [n.id, n]));
  const out = {};
  g.edges.forEach((e) => { (out[e.sourceNodeId] ||= []).push(e); });

  const triggers = g.nodes.filter((n) => n.type === 'trigger' || n.type === 'circle');
  if (triggers.length !== 1) {
    add(null, 'A workflow requires exactly one Trigger start event.');
  }

  g.nodes.forEach((n) => {
    if (['sticky_note', 'annotation'].includes(n.type)) return;
    const isKnown = TYPES[n.type] || ['circle', 'rect', 'diamond'].includes(n.type);
    if (!isKnown) add(n.id, `Unknown node type "${n.type}".`);

    const title = (n.properties && n.properties.title) || n.id;
    const o = out[n.id] || [];
    const actionType = n.type === 'rect' ? ((n.properties && (n.properties.action || n.properties.action_type)) || '') : n.type;

    if ((n.type === 'trigger' || n.type === 'circle') && !o.length) {
      add(n.id, `Trigger "${title}" has no outgoing connection. Connect it to an initial step.`);
    }

    if (TERMINAL.includes(actionType) && o.length) {
      add(n.id, `Terminal step "${title}" marks the end of the workflow and cannot have outgoing connections.`);
    }

    if (n.type === 'decision' || n.type === 'diamond') {
      const br = o.map((e) => {
        let b = (e.properties || {}).branch;
        if (!b) {
          const tv = (typeof e.text === 'string' ? e.text : (e.text && e.text.value) || '').toLowerCase().trim();
          if (tv.includes('yes')) b = 'yes';
          else if (tv.includes('no')) b = 'no';
        }
        return b;
      });
      if (!br.includes('yes')) add(n.id, `Decision "${title}" is missing a connected "YES" branch.`);
      if (!br.includes('no')) add(n.id, `Decision "${title}" is missing a connected "NO" branch.`);
    } else if (n.type !== 'trigger' && n.type !== 'circle' && !TERMINAL.includes(actionType) && !o.length) {
      add(n.id, `Step "${title}" has no outgoing connection. Connect it to a next step or terminal archive.`);
    }
  });

  if (triggers.length === 1) {
    const seen = new Set(), stack = [triggers[0].id];
    while (stack.length) {
      const id = stack.pop();
      if (seen.has(id)) continue;
      seen.add(id);
      (out[id] || []).forEach((e) => stack.push(e.targetNodeId));
    }
    g.nodes.forEach((n) => {
      if (['sticky_note', 'annotation'].includes(n.type)) return;
      if (!seen.has(n.id)) {
        const title = (n.properties && n.properties.title) || n.id;
        add(n.id, `Step "${title}" is not reachable from the trigger start event.`);
      }
    });
  }

  const color = {};
  const cyc = (id) => {
    color[id] = 1;
    for (const e of out[id] || []) {
      const t = e.targetNodeId;
      if (color[t] === 1) return true;
      if (!color[t] && nodes[t] && cyc(t)) return true;
    }
    color[id] = 2;
    return false;
  };
  for (const n of g.nodes) {
    if (!color[n.id] && cyc(n.id)) {
      const title = (n.properties && n.properties.title) || n.id;
      add(n.id, `Workflow cycle detected: Step "${title}" loops back onto itself.`);
      break;
    }
  }

  return errs;
}
