import * as LFmod from '@logicflow/core';
import '@logicflow/core/dist/index.css';
import { MiniMap, Snapshot } from '@logicflow/extension';
import '@logicflow/extension/dist/index.css';
import '../../css/workflow.css';
import { TYPES, GROUPS, SCHEMA, metaOf, newProps, defaultGraph, ACTION_COLORS, anchor } from './nodes';
import { createApi } from './api';
import { validateGraph } from './validate';

const LogicFlow = LFmod.LogicFlow || LFmod.default;
const {
  CircleNode, CircleNodeModel,
  RectNode, RectNodeModel,
  DiamondNode, DiamondNodeModel,
  PolylineEdge, PolylineEdgeModel,
  h,
} = LFmod;

const root = document.getElementById('wf-app');
const $ = (id) => document.getElementById(id);
const api = createApi(root);
const data = JSON.parse($('wf-data').textContent);
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const say = (s) => { $('status').textContent = s; };
const truncate = (s, len) => {
  const str = String(s || '');
  return str.length > len ? str.slice(0, len - 1) + '…' : str;
};

const FONT = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
const wrapText = (text, maxChars = 34, maxLines = 2) => {
  if (!text) return [];
  const words = String(text).trim().split(/\s+/);
  const lines = [];
  let cur = '';
  for (const w of words) {
    if (!cur) {
      cur = w;
    } else if ((cur + ' ' + w).length <= maxChars) {
      cur += ' ' + w;
    } else {
      lines.push(cur);
      cur = w;
      if (lines.length >= maxLines - 1) break;
    }
  }
  if (cur && lines.length < maxLines) {
    lines.push(cur);
  }
  return lines;
};

/* ---------- SVG BPMN Native Shapes ---------- */

// Trigger: BPMN Start Event (Circle)
class TriggerModel extends CircleNodeModel {
  setAttributes() {
    this.r = 48;
    this.targetRules.push({ message: 'A trigger cannot have incoming connections', validate: () => false });
  }
  getOuterGAttributes() {
    const p = this.properties || {};
    const title = p.title || 'Lead Created';
    const desc = p.desc || 'A new lead enters the CRM from a web form, import or API.';
    return {
      'data-nid': this.id,
      className: 'wf-node-g wf-node-trigger',
      role: 'graphics-symbol',
      'aria-label': `${title}: Start event trigger. ${desc}`,
      tabIndex: 0,
    };
  }
}

class TriggerView extends CircleNode {
  getShape() {
    const { model } = this.props;
    const { x, y, r } = model;
    const p = model.properties || {};
    const title = p.title || 'Lead Created';
    const desc = p.desc || 'A new lead enters the CRM from a web form, import or API.';

    return h('g', { className: 'wf-shape wf-trigger' }, [
      h('title', {}, `${title} (BPMN Start Event)\n${desc}`),
      h('desc', {}, desc),
      h('circle', {
        className: 'wf-main-shape',
        cx: x,
        cy: y,
        r,
        fill: '#f5f3ff',
        stroke: '#7c3aed',
        strokeWidth: 2.5,
      }),
      h('circle', {
        cx: x,
        cy: y,
        r: r - 5,
        fill: 'transparent',
        stroke: '#c4b5fd',
        strokeWidth: 1.2,
        strokeDasharray: '3 2',
      }),
      h('circle', {
        cx: x,
        cy: y - 16,
        r: 10,
        fill: '#7c3aed',
      }),
      h('path', {
        d: 'M -3.5 -5 L 4.5 0 L -3.5 5 Z',
        transform: `translate(${x + 1}, ${y - 16})`,
        fill: '#ffffff',
      }),
      h('text', {
        x,
        y: y + 9,
        textAnchor: 'middle',
        fill: '#1e1b4b',
        fontSize: 11.5,
        fontWeight: 700,
        fontFamily: FONT,
      }, truncate(title, 15)),
      h('text', {
        x,
        y: y + 23,
        textAnchor: 'middle',
        fill: '#6d28d9',
        fontSize: 8.5,
        fontWeight: 700,
        fontFamily: FONT,
        letterSpacing: '0.06em',
      }, 'START EVENT'),
    ]);
  }
}

// Decision: BPMN Gateway (Diamond)
class DecisionModel extends DiamondNodeModel {
  setAttributes() {
    this.rx = 120;
    this.ry = 75;
  }
  getOuterGAttributes() {
    const p = this.properties || {};
    const title = p.title || 'Lead Qualified?';
    const rawMeta = p.meta || 'Rule: score \u2265 60';
    const ruleText = rawMeta.replace('>=', '\u2265').replace('<=', '\u2264');
    return {
      'data-nid': this.id,
      className: 'wf-node-g wf-node-decision',
      role: 'graphics-symbol',
      'aria-label': `${title}: Decision gateway evaluating ${ruleText}. Branches: Yes or No.`,
      tabIndex: 0,
    };
  }
}

class DecisionView extends DiamondNode {
  getShape() {
    const { model } = this.props;
    const { points, x, y } = model;
    const pts = (points || []).map((p) => p.join(',')).join(' ');
    const p = model.properties || {};
    const title = p.title || 'Lead Qualified?';
    const rawMeta = p.meta || 'Rule: score \u2265 60';
    const ruleText = rawMeta.replace('>=', '\u2265').replace('<=', '\u2264');
    const desc = p.desc || 'Checks a lead field before conversion.';
    const mw = Math.min(116, Math.max(72, ruleText.length * 5.6 + 12));

    return h('g', { className: 'wf-shape wf-decision' }, [
      h('title', {}, `${title} \u2014 ${ruleText}\nDescription: ${desc}\nBranches: \u2713 Yes (qualified) \u2192 Convert | \u2715 No (disqualified) \u2192 Disqualify`),
      h('desc', {}, `Decision gateway evaluating: ${ruleText}`),
      // Main Diamond Shape
      h('polygon', {
        className: 'wf-main-shape',
        points: pts,
        fill: '#fffbeb',
        stroke: '#d97706',
        strokeWidth: 2.5,
      }),
      // Top Gateway Icon: Branching Fork / Condition Split (Placed at y - 36 for clean spacing)
      h('circle', {
        cx: x,
        cy: y - 36,
        r: 9.5,
        fill: '#fef3c7',
        stroke: '#d97706',
        strokeWidth: 1.5,
      }),
      h('path', {
        d: 'M 0 -4.5 L 0 -1 M 0 -1 C -1 0.5, -2.5 1.8, -3.5 3.5 M 0 -1 C 1 0.5, 2.5 1.8, 3.5 3.5',
        transform: `translate(${x}, ${y - 36})`,
        stroke: '#b45309',
        strokeWidth: 1.8,
        strokeLinecap: 'round',
        fill: 'none',
      }),
      h('circle', {
        cx: x,
        cy: y - 36 - 4.5,
        r: 1.4,
        fill: '#b45309',
      }),
      h('circle', {
        cx: x - 3.5,
        cy: y - 36 + 3.5,
        r: 1.4,
        fill: '#b45309',
      }),
      h('circle', {
        cx: x + 3.5,
        cy: y - 36 + 3.5,
        r: 1.4,
        fill: '#b45309',
      }),
      // Tag (Cleanly separated below icon circle with 7.5px clear space)
      h('text', {
        x,
        y: y - 12,
        textAnchor: 'middle',
        fill: '#b45309',
        fontSize: 8.5,
        fontWeight: 700,
        fontFamily: FONT,
        letterSpacing: '0.06em',
      }, 'DECISION GATEWAY'),
      // Title
      h('text', {
        x,
        y: y + 4,
        textAnchor: 'middle',
        fill: '#78350f',
        fontSize: 12.5,
        fontWeight: 700,
        fontFamily: FONT,
      }, truncate(title, 20)),
      // Centered Rule Pill (Safely within diamond boundary)
      h('rect', {
        x: x - mw / 2,
        y: y + 16,
        width: mw,
        height: 18,
        rx: 9,
        ry: 9,
        fill: '#fde68a',
        stroke: '#f59e0b',
        strokeWidth: 1,
      }),
      h('text', {
        x,
        y: y + 28.5,
        textAnchor: 'middle',
        fill: '#92400e',
        fontSize: 9.5,
        fontWeight: 700,
        fontFamily: FONT,
      }, truncate(ruleText, 18)),
    ]);
  }
}

// Sticky Note / Canvas Annotation
class StickyNoteModel extends RectNodeModel {
  setAttributes() {
    this.width = 190;
    this.height = 110;
  }
  getOuterGAttributes() {
    const p = this.properties || {};
    const text = (p.config && p.config.note) || p.desc || 'Workflow annotation note';
    return {
      'data-nid': this.id,
      className: 'wf-node-g wf-node-sticky',
      role: 'note',
      'aria-label': `Sticky Note Annotation: ${text}`,
      tabIndex: 0,
    };
  }
}

class StickyNoteView extends RectNode {
  getShape() {
    const { model } = this.props;
    const { x, y, width, height } = model;
    const p = model.properties || {};
    const cfg = p.config || {};
    const noteText = cfg.note || p.desc || 'Click to write annotation or comment...';
    const noteColor = cfg.color || '#fef08a';
    const leftX = x - width / 2;
    const topY = y - height / 2;
    const fold = 18;
    const lines = wrapText(noteText, 25, 4);

    return h('g', { className: 'wf-shape wf-sticky' }, [
      h('title', {}, `Annotation Note:\n${noteText}`),
      h('path', {
        className: 'wf-sticky-rect',
        d: `M ${leftX} ${topY} L ${leftX + width - fold} ${topY} L ${leftX + width} ${topY + fold} L ${leftX + width} ${topY + height} L ${leftX} ${topY + height} Z`,
        fill: noteColor,
        stroke: '#ca8a04',
        strokeWidth: 1.2,
      }),
      h('path', {
        d: `M ${leftX + width - fold} ${topY} L ${leftX + width - fold} ${topY + fold} L ${leftX + width} ${topY + fold} Z`,
        fill: '#fde047',
        stroke: '#ca8a04',
        strokeWidth: 1,
      }),
      h('circle', {
        cx: leftX + 16,
        cy: topY + 14,
        r: 3.5,
        fill: '#e11d48',
      }),
      h('text', {
        x: leftX + 26,
        y: topY + 17,
        fill: '#854d0e',
        fontSize: 9.5,
        fontWeight: 700,
        fontFamily: FONT,
        letterSpacing: '0.04em',
      }, 'NOTE / ANNOTATION'),
      ...lines.map((line, idx) =>
        h('text', {
          x: leftX + 14,
          y: topY + 38 + idx * 16,
          fill: '#713f12',
          fontSize: 10.5,
          fontWeight: 500,
          fontFamily: FONT,
        }, line)
      ),
    ]);
  }
}

// Action: BPMN Task / Activity (Rounded Rectangle)
const ACTION_ICONS = {
  assign:     'M 0 -4 A 3 3 0 1 0 0 -10 A 3 3 0 1 0 0 -4 Z M -5 4 A 5 4 0 0 1 5 4 Z',
  activity:   'M -4 -4 L 4 -4 L 4 2 L 0 5 L -4 2 Z',
  stage:      'M -4 -6 L 4 -3 L -4 0 Z M -4 -6 L -4 6',
  convert:    'M -4 0 L -1 3 L 5 -3',
  account:    'M -5 5 L -5 -5 L 5 -5 L 5 5 Z M -2 -2 L 2 -2',
  disqualify: 'M -3 -3 L 3 3 M 3 -3 L -3 3',
  archive:    'M -5 -3 L 5 -3 L 5 4 L -5 4 Z M -5 -3 L -3 -5 L 3 -5 L 5 -3',
};

class ActionModel extends RectNodeModel {
  setAttributes() {
    this.width = 240;
    this.height = 96;
    this.radius = 10;
    if (this.type === 'archive') {
      this.sourceRules.push({ message: 'Archive is the end of the flow', validate: () => false });
    }
  }
  getOuterGAttributes() {
    const p = this.properties || {};
    const actualType = this.type === 'rect' ? (p.action || 'assign') : this.type;
    const title = p.title || (TYPES[actualType] && TYPES[actualType].title) || actualType;
    const desc = p.desc || (TYPES[actualType] && TYPES[actualType].desc) || '';
    const meta = p.meta || '';
    return {
      'data-nid': this.id,
      className: `wf-node-g wf-node-action wf-node-${actualType}`,
      role: 'graphics-symbol',
      'aria-label': `${title}: ${desc}. ${meta}`,
      tabIndex: 0,
    };
  }
}

class ActionView extends RectNode {
  getShape() {
    const { model } = this.props;
    const { x, y, width, height, radius } = model;
    const p = model.properties || {};
    const actualType = model.type === 'rect' ? (p.action || 'assign') : model.type;
    const d = TYPES[actualType] || { kind: 'Action', title: p.title || 'Action', desc: p.desc || '' };
    const color = ACTION_COLORS[actualType] || '#2563eb';
    const leftX = x - width / 2;
    const topY = y - height / 2;
    const title = p.title || d.title;
    const desc = p.desc || d.desc || '';
    const meta = p.meta || '';
    const isArchive = actualType === 'archive';
    const mw = Math.min(isArchive ? 115 : width - 50, Math.max(50, meta.length * 6 + 14));
    const iconPath = ACTION_ICONS[actualType] || ACTION_ICONS.assign;

    const children = [
      h('title', {}, `${title} (${d.kind || 'Action'})\nDescription: ${desc}\n${meta ? 'Config: ' + meta : ''}${isArchive ? '\n\u2022 End state: Archived leads are excluded from active reports' : ''}`),
      h('desc', {}, `${desc} ${meta}`),
      // Base Card
      h('rect', {
        className: 'wf-main-shape',
        x: leftX,
        y: topY,
        width,
        height,
        rx: radius,
        ry: radius,
        fill: '#ffffff',
        stroke: '#cbd5e1',
        strokeWidth: 1.5,
      }),
      // Left Color Accent Bar
      h('rect', {
        x: leftX,
        y: topY,
        width: 5,
        height,
        rx: 3,
        ry: 3,
        fill: color,
      }),
      // Icon Circle
      h('circle', {
        cx: leftX + 22,
        cy: topY + 22,
        r: 11,
        fill: `${color}18`,
        stroke: color,
        strokeWidth: 1,
      }),
      h('path', {
        d: iconPath,
        transform: `translate(${leftX + 22}, ${topY + 22})`,
        fill: color,
        stroke: color,
        strokeWidth: 0.5,
      }),
      // Header tag
      h('text', {
        x: leftX + 40,
        y: topY + 16,
        fill: color,
        fontSize: 9,
        fontWeight: 700,
        fontFamily: FONT,
        letterSpacing: '0.05em',
      }, (d.category || d.kind || 'ACTION').toUpperCase()),
    ];

    // Node Title - Supports clean two-line layout if title is long!
    const titleLines = wrapText(title, 24, 2);
    if (titleLines.length === 1) {
      children.push(
        h('text', {
          x: leftX + 40,
          y: topY + 31,
          fill: '#0f172a',
          fontSize: 12,
          fontWeight: 700,
          fontFamily: FONT,
        }, titleLines[0])
      );
    } else {
      children.push(
        h('text', {
          x: leftX + 40,
          y: topY + 28,
          fill: '#0f172a',
          fontSize: 11,
          fontWeight: 700,
          fontFamily: FONT,
        }, titleLines[0]),
        h('text', {
          x: leftX + 40,
          y: topY + 40,
          fill: '#0f172a',
          fontSize: 11,
          fontWeight: 700,
          fontFamily: FONT,
        }, titleLines[1])
      );
    }

    // Description Row:
    const descLines = isArchive
      ? ['Moves lead to cold nurture queue', '\u2022 Excluded from active reports']
      : wrapText(desc, 34, titleLines.length > 1 ? 1 : 2);

    const descStartY = titleLines.length > 1 ? topY + 54 : (descLines.length === 1 ? topY + 49 : topY + 46);
    descLines.forEach((line, idx) => {
      children.push(
        h('text', {
          x: leftX + 40,
          y: descStartY + idx * 13,
          fill: isArchive && idx === 1 ? '#b91c1c' : '#64748b',
          fontSize: 9.5,
          fontWeight: isArchive && idx === 1 ? 600 : 500,
          fontFamily: FONT,
        }, line)
      );
    });

    // Bottom Row: Meta Config Pill
    if (meta) {
      children.push(
        h('rect', {
          x: leftX + 40,
          y: topY + 70,
          width: mw,
          height: 18,
          rx: 4,
          ry: 4,
          fill: `${color}14`,
          stroke: `${color}28`,
          strokeWidth: 0.5,
        }),
        h('text', {
          x: leftX + 46,
          y: topY + 83,
          fill: color,
          fontSize: 9,
          fontWeight: 600,
          fontFamily: FONT,
        }, truncate(meta, 24))
      );
    }

    // Terminal End State Indicator for Archive
    if (isArchive) {
      children.push(
        h('g', { className: 'wf-end-state-indicator' }, [
          h('rect', {
            x: leftX + width - 66,
            y: topY + 70,
            width: 56,
            height: 18,
            rx: 9,
            ry: 9,
            fill: '#f1f5f9',
            stroke: '#94a3b8',
            strokeWidth: 1,
          }),
          // BPMN End Event Symbol (Thick double circle)
          h('circle', {
            cx: leftX + width - 54,
            cy: topY + 79,
            r: 5,
            fill: 'transparent',
            stroke: '#475569',
            strokeWidth: 1.8,
          }),
          h('circle', {
            cx: leftX + width - 54,
            cy: topY + 79,
            r: 2.2,
            fill: '#475569',
          }),
          h('text', {
            x: leftX + width - 32,
            y: topY + 83,
            fill: '#475569',
            fontSize: 8.5,
            fontWeight: 700,
            fontFamily: FONT,
            letterSpacing: '0.04em',
          }, 'END'),
        ])
      );
    }

    return h('g', { className: 'wf-shape wf-action' }, children);
  }
}

// Edge: BPMN Sequence Flow (Orthogonal Polyline)
class FlowPolylineModel extends PolylineEdgeModel {
  setAttributes() {
    this.offset = 24;
    this.customTextPosition = false;
  }
  initEdgeData(data) {
    super.initEdgeData(data);
    this.customTextPosition = false;
  }
  getTextPosition() {
    if (this.points && this.points.length >= 2) {
      let maxLen = -1;
      let mid = { x: 0, y: 0 };
      for (let i = 0; i < this.points.length - 1; i++) {
        const p1 = this.points[i];
        const p2 = this.points[i + 1];
        const dist = Math.hypot(p2.x - p1.x, p2.y - p1.y);
        if (dist > maxLen) {
          maxLen = dist;
          mid = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
        }
      }
      return mid;
    }
    return super.getTextPosition ? super.getTextPosition() : { x: 0, y: 0 };
  }
  getEdgeStyle() {
    const s = super.getEdgeStyle ? super.getEdgeStyle() : {};
    const b = (this.properties || {}).branch;
    const isYes = b === 'yes';
    const isNo = b === 'no';
    s.strokeWidth = 2.5;
    s.stroke = isYes ? '#16a34a' : isNo ? '#dc2626' : '#64748b';
    if (isNo) {
      // Color-blind accessibility: Dashed line pattern for NO / Disqualified branch
      s.strokeDasharray = '6 4';
    }
    return s;
  }
  getTextStyle() {
    const s = super.getTextStyle ? super.getTextStyle() : {};
    const b = (this.properties || {}).branch;
    const isYes = b === 'yes';
    const isNo = b === 'no';
    s.color = isYes ? '#15803d' : isNo ? '#b91c1c' : '#334155';
    s.fontSize = 11;
    s.fontWeight = 700;
    s.fontFamily = 'Figtree, ui-sans-serif, system-ui, sans-serif';
    s.background = {
      fill: isYes ? '#f0fdf4' : isNo ? '#fef2f2' : '#ffffff',
      stroke: isYes ? '#16a34a' : isNo ? '#dc2626' : '#cbd5e1',
      strokeWidth: 1.5,
      strokeDasharray: isNo ? '3 2' : 'none',
      rx: 11,
      ry: 11,
      wrapPadding: '4, 12',
    };
    return s;
  }
}

/* ---------- LogicFlow Setup ---------- */
const lf = new LogicFlow({
  container: $('lf'),
  plugins: [MiniMap, Snapshot],
  grid: { size: 20, visible: true, type: 'dot', config: { color: '#cbd5e1' } },
  keyboard: { enabled: true },
  history: true,
  snapline: true,
  nodeTextEdit: false,
  edgeTextEdit: false,
  edgeTextDraggable: false,
  adjustEdge: true,
  hoverOutline: false,
  background: { backgroundColor: 'transparent' },
});

// Register native BPMN shapes for both semantic names and native shape names
lf.register({ type: 'trigger', view: TriggerView, model: TriggerModel });
lf.register({ type: 'circle', view: TriggerView, model: TriggerModel });
lf.register({ type: 'decision', view: DecisionView, model: DecisionModel });
lf.register({ type: 'diamond', view: DecisionView, model: DecisionModel });

Object.keys(TYPES).forEach((t) => {
  if (t !== 'trigger' && t !== 'decision' && t !== 'sticky_note') {
    lf.register({ type: t, view: ActionView, model: ActionModel });
  }
});
lf.register({ type: 'rect', view: ActionView, model: ActionModel });
lf.register({ type: 'sticky_note', view: StickyNoteView, model: StickyNoteModel });

lf.register({ type: 'polyline', view: PolylineEdge, model: FlowPolylineModel });
lf.register({ type: 'flow-edge', view: PolylineEdge, model: FlowPolylineModel });
lf.setDefaultEdgeType('polyline');

lf.setTheme({
  anchor: {
    fill: '#ffffff',
    stroke: '#64748b',
    strokeWidth: 1.5,
    r: 5,
    hover: { fill: '#7c3aed', stroke: '#7c3aed', r: 7 },
  },
  polyline: {
    stroke: '#64748b',
    strokeWidth: 2,
    offset: 24,
  },
  arrow: {
    offset: 8,
    verticalLength: 5,
  },
  edgeText: {
    color: '#334155',
    fontSize: 11,
    background: { fill: '#ffffff', stroke: '#cbd5e1', rx: 11, ry: 11, wrapPadding: '4, 12' },
  },
});

/* ---------- Autosave with optimistic locking ---------- */
let revision = data.revision, ready = false, dirty = false, saving = false, conflict = false, ver = 0, timer = null;
const setSave = (s) => { $('wf-save').textContent = s; };

function clean() {
  const g = lf.getGraphData();
  g.nodes = (g.nodes || []).map((n) => ({
    id: n.id,
    type: n.type,
    x: n.x,
    y: n.y,
    properties: {
      title: (n.properties && n.properties.title) || '',
      desc: (n.properties && n.properties.desc) || '',
      meta: (n.properties && n.properties.meta) || '',
      config: (n.properties && n.properties.config) || {},
      action: (n.properties && (n.properties.action || n.properties.action_type)) || undefined,
    },
  }));
  g.edges = (g.edges || []).map((e) => {
    let branch = (e.properties && e.properties.branch) || '';
    const textVal = (typeof e.text === 'string' ? e.text : (e.text && e.text.value)) || '';
    if (!branch && textVal) {
      const tv = textVal.toLowerCase().trim();
      if (tv.includes('yes')) branch = 'yes';
      else if (tv.includes('no')) branch = 'no';
    }
    return {
      id: e.id,
      type: e.type || 'polyline',
      sourceNodeId: e.sourceNodeId,
      targetNodeId: e.targetNodeId,
      sourceAnchorId: e.sourceAnchorId,
      targetAnchorId: e.targetAnchorId,
      startPoint: e.startPoint,
      endPoint: e.endPoint,
      properties: { ...(e.properties || {}), branch },
      ...(textVal ? { text: { value: textVal } } : {}),
    };
  });
  return g;
}

function queueSave() {
  if (!ready || conflict) return;
  dirty = true; ver++; setSave('Unsaved changes');
  clearTimeout(timer); timer = setTimeout(flush, 1200);
  scheduleValidate();
}

async function flush() {
  clearTimeout(timer);
  if (conflict) return false;
  if (!dirty && !saving) return true;
  if (saving) {
    while (saving) { await sleep(100); }
    if (conflict) return false;
    if (!dirty) return true;
  }
  saving = true; setSave('Saving…');
  const mine = ver;
  try {
    const r = await api.save({ revision, graph: clean() });
    revision = r.revision;
    if (ver === mine) { dirty = false; setSave('Saved'); } else { saving = false; return flush(); }
    return true;
  } catch (e) {
    if (e.status === 409) { conflict = true; setSave('Out of date: reload'); say('This workflow was changed elsewhere. Reload the page to get the latest version.'); }
    else { setSave('Save failed, retrying…'); timer = setTimeout(flush, 5000); }
    return false;
  } finally { saving = false; }
}
window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

/* ---------- Validation badges ---------- */
let vt = null, issues = [];
function scheduleValidate() { clearTimeout(vt); vt = setTimeout(runValidate, 350); }
function mark(ids, cls) {
  document.querySelectorAll('.wf-node-g').forEach((el) => {
    el.classList.toggle(cls, ids.includes(el.dataset.nid));
  });
}
function runValidate() {
  issues = validateGraph(lf.getGraphData());
  mark(issues.map((i) => i.node).filter(Boolean), 'bad');
  const b = $('wf-issues'); b.hidden = !issues.length; b.textContent = `${issues.length} issue${issues.length === 1 ? '' : 's'}`;
  b.title = issues.map((i) => i.message).join('\n');
  return issues;
}

/* ---------- Component Icons ---------- */
const ICONS = {
  trigger: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`,
  assign: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M20 8v6M23 11h-6"/></svg>`,
  activity: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>`,
  stage: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>`,
  decision: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>`,
  convert: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>`,
  account: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`,
  disqualify: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>`,
  archive: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>`,
  sticky_note: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M15.5 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.5L15.5 3z"/><path d="M15 3v6h6"/></svg>`,
  edge: `<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="12" r="3"/><circle cx="18" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6"/></svg>`,
  trash: `<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>`,
};

/* ---------- Palette Rendering ---------- */
function renderPalette() {
  const pal = $('pal');
  pal.innerHTML = `
    <div class="pal-header">
      <div class="pal-title">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
        <span>Components</span>
      </div>
      <span class="pal-count">${Object.keys(TYPES).length}</span>
    </div>
    <div class="pal-search-wrap">
      <svg class="pal-search-icon" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input type="text" id="pal-search" class="pal-search" placeholder="Filter nodes...">
    </div>
    <div id="pal-groups"></div>
    <div class="pal-tip">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      <span>Drag nodes onto canvas. Hold <kbd>Shift</kbd> to multi-select.</span>
    </div>
  `;

  const groupsContainer = $('pal-groups');
  GROUPS.forEach(([name, types]) => {
    const groupEl = document.createElement('div');
    groupEl.className = 'pal-group';
    groupEl.innerHTML = `
      <div class="pal-group-head" title="Click to collapse / expand">
        <span class="pal-group-title">${esc(name)}</span>
        <div style="display: flex; align-items: center; gap: 4px;">
          <span class="pal-group-pill">${types.length}</span>
          <span class="pal-chevron">▾</span>
        </div>
      </div>
      <div class="pal-items"></div>
    `;

    groupEl.querySelector('.pal-group-head').addEventListener('click', () => {
      groupEl.classList.toggle('collapsed');
    });

    const itemsContainer = groupEl.querySelector('.pal-items');

    types.forEach((t) => {
      const typeDef = TYPES[t] || {};
      const color = ACTION_COLORS[t] || '#4f46e5';
      const icon = ICONS[t] || ICONS.trigger;
      const schemaFields = SCHEMA[t] || [];
      const reqText = schemaFields.length
        ? `Config: ${schemaFields.map((f) => f.label.split('(')[0].trim()).join(', ')}`
        : 'Parameters: Core Lifecycle Event';

      const d = document.createElement('div');
      d.className = `pi t-${t}`;
      d.dataset.type = t;
      d.dataset.title = (typeDef.title || '').toLowerCase();
      d.dataset.category = (typeDef.category || '').toLowerCase();
      d.title = `Drag "${typeDef.title}" onto canvas`;
      d.innerHTML = `
        <div class="pi-icon-wrap" style="background: ${color}14; color: ${color};">
          ${icon}
        </div>
        <div class="pi-body">
          <div class="pi-title">${esc(typeDef.title)}</div>
          <div class="pi-cat">${esc(typeDef.category || typeDef.kind)}</div>
        </div>
        <div class="pi-handle" title="Drag handle">
          <svg width="6" height="10" viewBox="0 0 6 10" fill="currentColor">
            <circle cx="1.5" cy="2" r="1"/><circle cx="4.5" cy="2" r="1"/>
            <circle cx="1.5" cy="5" r="1"/><circle cx="4.5" cy="5" r="1"/>
            <circle cx="1.5" cy="8" r="1"/><circle cx="4.5" cy="8" r="1"/>
          </svg>
        </div>
        <div class="pi-tooltip">
          <div class="pi-tooltip-title">
            <span style="color: ${color}; display: inline-flex;">${icon}</span>
            <span>${esc(typeDef.title)}</span>
          </div>
          <div class="pi-tooltip-cat">${esc(typeDef.category || typeDef.kind)} · BPMN ${esc(typeDef.kind || 'Action')}</div>
          <div class="pi-tooltip-desc">${esc(typeDef.desc)}</div>
          <div class="pi-tooltip-req">${esc(reqText)}</div>
        </div>
      `;
      d.addEventListener('mousedown', () => {
        lf.dnd.startDrag({ type: t, properties: newProps(t) });
      });
      itemsContainer.appendChild(d);
    });

    groupsContainer.appendChild(groupEl);
  });

  const searchInput = $('pal-search');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      document.querySelectorAll('.pi').forEach((el) => {
        const matches = !q || el.dataset.title.includes(q) || el.dataset.category.includes(q) || el.dataset.type.includes(q);
        el.style.display = matches ? 'flex' : 'none';
      });
      document.querySelectorAll('.pal-group').forEach((grp) => {
        const hasVisible = Array.from(grp.querySelectorAll('.pi')).some((el) => el.style.display !== 'none');
        grp.style.display = hasVisible ? 'block' : 'none';
      });
    });
  }
}
renderPalette();

/* ---------- Inspector ---------- */
const insp = $('insp');
window.__selectIssue = (nodeId) => {
  if (!nodeId) return;
  const m = lf.getNodeModelById(nodeId);
  if (m) {
    showNode(nodeId);
    try { lf.selectNodeById(nodeId); } catch (_) {}
  }
};

function addStickyNoteOnCanvas() {
  const t = lf.getTransform();
  const cx = (-t.TRANSLATE_X + 450) / t.SCALE_X;
  const cy = (-t.TRANSLATE_Y + 260) / t.SCALE_Y;
  const id = 'note_' + Math.random().toString(36).slice(2, 7);
  const n = {
    id,
    type: 'sticky_note',
    x: Math.round(cx),
    y: Math.round(cy),
    properties: newProps('sticky_note'),
  };
  lf.addNode(n);
  showNode(id);
  queueSave();
  say('Sticky Note annotation added to canvas.');
}

function emptyInspector() {
  const g = lf.getGraphData();
  const nodes = g.nodes || [];
  const edges = g.edges || [];
  const actionCount = nodes.filter((n) => n.type !== 'trigger' && n.type !== 'circle' && n.type !== 'decision' && n.type !== 'diamond' && n.type !== 'sticky_note').length;
  const decisionCount = nodes.filter((n) => n.type === 'decision' || n.type === 'diamond').length;

  let issuesHtml = '';
  if (issues.length) {
    issuesHtml = `
      <div class="insp-issues-card">
        <div class="insp-issues-head">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <span>Validation Warnings (${issues.length})</span>
        </div>
        <div class="insp-issues-list">
          ${issues.map((i) => `
            <div class="insp-issue-item" onclick="window.__selectIssue && window.__selectIssue('${esc(i.node || '')}')" title="Click to locate node">
              <span style="font-weight: 600; color: #dc2626;">•</span>
              <span>${esc(i.message)}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  } else {
    issuesHtml = `
      <div style="margin-top: 10px; padding: 8px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 11px; color: #166534; display: flex; align-items: center; gap: 6px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>All nodes and routing paths are valid and ready to publish.</span>
      </div>
    `;
  }

  insp.innerHTML = `
    <div class="insp-empty-wrap">
      <div class="insp-empty-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="4" stroke-dasharray="3 3"/><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
      </div>
      <div class="insp-empty-title">Workflow Inspector & Stats</div>
      <p class="insp-empty-desc">Click any node or connector on the canvas to configure parameters, automation rules, or branches.</p>

      <div class="insp-stats-box" style="margin-bottom: 8px;">
        <div class="insp-stat-item">
          <span class="insp-stat-num">${nodes.length}</span>
          <span class="insp-stat-label">Total Nodes</span>
        </div>
        <div class="insp-stat-div"></div>
        <div class="insp-stat-item">
          <span class="insp-stat-num">${actionCount}</span>
          <span class="insp-stat-label">Actions</span>
        </div>
        <div class="insp-stat-div"></div>
        <div class="insp-stat-item">
          <span class="insp-stat-num">${decisionCount}</span>
          <span class="insp-stat-label">Decisions</span>
        </div>
        <div class="insp-stat-div"></div>
        <div class="insp-stat-item">
          <span class="insp-stat-num">${edges.length}</span>
          <span class="insp-stat-label">Links</span>
        </div>
      </div>

      <div style="font-size: 11px; font-weight: 600; color: var(--mute); margin-top: 10px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Quick Actions</div>
      <div class="insp-quick-actions">
        <button type="button" class="insp-qa-btn" id="qa-add-note" title="Add Sticky Note to canvas">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2.2"><path d="M15.5 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.5L15.5 3z"/><path d="M15 3v6h6"/></svg>
          <span>+ Sticky Note</span>
        </button>
        <button type="button" class="insp-qa-btn" id="qa-autolayout" title="Auto untangle nodes">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--pri)" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
          <span>Auto-Layout</span>
        </button>
        <button type="button" class="insp-qa-btn" id="qa-export-json" title="Download JSON blueprint">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <span>Export JSON</span>
        </button>
        <button type="button" class="insp-qa-btn" id="qa-simulate" title="Run test simulation">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <span>Simulate Flow</span>
        </button>
      </div>

      ${issuesHtml}

      <div style="margin-top: 12px; padding: 10px; background: #f8fafc; border: 1px dashed var(--line); border-radius: 6px; font-size: 11px; color: var(--mute); line-height: 1.4;">
        <strong style="color: var(--ink);">Lead Blueprint Tip:</strong>
        Use dynamic tokens such as <code style="color: var(--pri); font-weight: 600;">{{lead.name}}</code> or <code style="color: var(--pri); font-weight: 600;">{{lead.score}}</code> in task subjects and notifications.
      </div>
    </div>
  `;

  $('qa-add-note').onclick = addStickyNoteOnCanvas;
  $('qa-autolayout').onclick = autoLayoutGraph;
  $('qa-export-json').onclick = exportWorkflowJson;
  $('qa-simulate').onclick = () => $('sim').click();
}

const SAMPLES = {
  yes: {
    name: 'Sarah Jenkins',
    company: 'CloudScale Technologies',
    email: 'sarah.jenkins@cloudscale.io',
    score: 85,
    status: 'new',
    stage: 'new',
    source: 'web',
    country: 'US',
  },
  no: {
    name: 'David Miller',
    company: "Dave's Hardware",
    email: 'dave@daveshardware.com',
    score: 25,
    status: 'new',
    stage: 'new',
    source: 'web',
    country: 'US',
  },
};

const LEAD_TOKENS = [
  { token: '{{lead.name}}', label: 'name' },
  { token: '{{lead.company}}', label: 'company' },
  { token: '{{lead.email}}', label: 'email' },
  { token: '{{lead.score}}', label: 'score' },
  { token: '{{lead.stage}}', label: 'stage' },
  { token: '{{lead.source}}', label: 'source' },
  { token: '{{lead.country}}', label: 'country' },
];

function resolveTokens(text) {
  if (!text || typeof text !== 'string') return '';
  const sel = $('outcome');
  const sample = SAMPLES[sel ? sel.value : 'yes'] || SAMPLES.yes;
  return text.replace(/\{\{\s*(?:lead\.)?([a-z0-9_]+)\s*\}\}/gi, (match, field) => {
    const key = field.toLowerCase();
    return sample[key] !== undefined ? String(sample[key]) : match;
  });
}

function fieldHtml(f, v) {
  const val = esc(v ?? '');
  if (f.type === 'select') {
    return `
      <div class="insp-field">
        <label>${esc(f.label)}</label>
        <select data-k="${f.key}">
          ${f.options.map(([k, l]) => `<option value="${esc(k)}"${String(v) === String(k) ? ' selected' : ''}>${esc(l)}</option>`).join('')}
        </select>
      </div>
    `;
  }
  if (f.type === 'textarea') {
    return `
      <div class="insp-field" data-field-wrap="${esc(f.key)}">
        <label>${esc(f.label)}</label>
        <textarea data-k="${f.key}" rows="3" style="resize: vertical; font-family: inherit; font-size: 11.5px;">${val}</textarea>
      </div>
    `;
  }
  const isTemplateField = f.type !== 'number';
  const resolved = isTemplateField && String(v || '').includes('{{') ? resolveTokens(String(v || '')) : '';
  return `
    <div class="insp-field" data-field-wrap="${esc(f.key)}">
      <label>${esc(f.label)}</label>
      <input type="${f.type === 'number' ? 'number' : 'text'}" data-k="${f.key}" value="${val}">
      ${isTemplateField ? `
        <div class="lead-tokens-bar">
          <div class="lead-tokens-title">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Insert Dynamic Lead Token:</span>
          </div>
          ${LEAD_TOKENS.map((t) => `<button type="button" class="token-chip" data-insert="${esc(t.token)}" data-target="${esc(f.key)}" title="Insert ${esc(t.token)} at cursor">+ ${esc(t.label)}</button>`).join('')}
        </div>
        <div class="token-live-preview" id="preview-${esc(f.key)}" ${resolved ? '' : 'style="display:none;"'}>
          <div class="token-live-preview-head">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Live Blueprint Preview</span>
          </div>
          <div class="token-live-preview-body">${esc(resolved)}</div>
        </div>
      ` : ''}
    </div>
  `;
}

function showNode(id) {
  const m = lf.getNodeModelById(id); if (!m) return;
  const p = m.properties || {};
  const actualType = (m.type === 'circle' ? 'trigger' : m.type === 'diamond' ? 'decision' : m.type === 'rect' ? (p.action || 'assign') : m.type);
  const typeDef = TYPES[actualType] || TYPES[m.type] || { kind: 'Node', title: p.title, category: 'Node' };
  const color = ACTION_COLORS[actualType] || '#4f46e5';
  const icon = ICONS[actualType] || ICONS.trigger;
  const fields = SCHEMA[actualType] || SCHEMA[m.type] || [];

  insp.innerHTML = `
    <div class="insp-header">
      <div class="insp-header-left">
        <div class="insp-badge-icon" style="background: ${color}14; color: ${color};">
          ${icon}
        </div>
        <div class="insp-header-meta">
          <div class="insp-node-type">${esc(typeDef.kind)} • ${esc(typeDef.category || 'Node')}</div>
          <div class="insp-node-id">#${esc(id)}</div>
        </div>
      </div>
      <button class="insp-close-btn" id="insp-close" title="Deselect">&times;</button>
    </div>

    <div class="insp-section">
      <div class="insp-section-title">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <span>General Information</span>
      </div>
      <div class="insp-field">
        <label>Node Title</label>
        <input type="text" id="f-title" value="${esc(p.title)}" placeholder="e.g. Route SDR Assignment">
      </div>
      <div class="insp-field">
        <label>Description & Purpose</label>
        <textarea id="f-desc" placeholder="Details about this step in the lead lifecycle...">${esc(p.desc)}</textarea>
      </div>
    </div>

    ${fields.length ? `
      <div class="insp-section">
        <div class="insp-section-title">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Automation Configuration</span>
        </div>
        ${fields.map((f) => fieldHtml(f, (p.config || {})[f.key])).join('')}
      </div>
    ` : ''}

    <div class="insp-section" style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--line);">
      <button class="btn-delete-node" id="del">
        ${ICONS.trash}
        <span>Delete Node</span>
      </button>
    </div>
  `;

  const updatePreview = (key, text) => {
    const previewEl = $(`preview-${key}`);
    if (!previewEl) return;
    if (String(text).includes('{{')) {
      const resolved = resolveTokens(text);
      const body = previewEl.querySelector('.token-live-preview-body');
      if (body) body.textContent = resolved;
      previewEl.style.display = 'block';
    } else {
      previewEl.style.display = 'none';
    }
  };

  $('f-title').oninput = (e) => { lf.setProperties(id, { title: e.target.value }); queueSave(); };
  $('f-desc').oninput = (e) => { lf.setProperties(id, { desc: e.target.value }); queueSave(); };
  $('insp-close').onclick = () => { emptyInspector(); };

  insp.querySelectorAll('.token-chip').forEach((chip) => {
    chip.addEventListener('click', (e) => {
      e.preventDefault();
      const targetKey = chip.dataset.target;
      const token = chip.dataset.insert;
      const input = insp.querySelector(`[data-k="${targetKey}"]`);
      if (!input) return;

      const start = input.selectionStart ?? input.value.length;
      const end = input.selectionEnd ?? input.value.length;
      const oldVal = input.value;
      input.value = oldVal.substring(0, start) + token + oldVal.substring(end);
      input.focus();
      input.setSelectionRange(start + token.length, start + token.length);

      input.dispatchEvent(new Event('input'));
    });
  });

  insp.querySelectorAll('[data-k]').forEach((el) => {
    el.addEventListener('input', () => {
      const key = el.dataset.k;
      const cfg = { ...((lf.getNodeModelById(id).properties || {}).config || {}), [key]: el.value };
      const props = { config: cfg, meta: metaOf(actualType, cfg) };
      if (actualType === 'sticky_note' && key === 'note') {
        props.desc = el.value;
      }
      lf.setProperties(id, props);
      updatePreview(key, el.value);
      queueSave();
    });
  });
  $('del').onclick = () => { lf.deleteNode(id); emptyInspector(); queueSave(); };
}

function showEdge(id) {
  const m = lf.getEdgeModelById(id); if (!m) return;
  const currText = m.text && (typeof m.text === 'object' ? m.text.value : m.text);
  const src = lf.getNodeModelById(m.sourceNodeId);
  const tgt = lf.getNodeModelById(m.targetNodeId);
  const srcTitle = (src && src.properties && src.properties.title) || 'Source';
  const tgtTitle = (tgt && tgt.properties && tgt.properties.title) || 'Target';

  insp.innerHTML = `
    <div class="insp-header">
      <div class="insp-header-left">
        <div class="insp-badge-icon" style="background: #eef2ff; color: var(--pri);">
          ${ICONS.edge}
        </div>
        <div class="insp-header-meta">
          <div class="insp-node-type">CONNECTOR • POLYLINE</div>
          <div class="insp-node-id">#${esc(id)}</div>
        </div>
      </div>
      <button class="insp-close-btn" id="insp-close" title="Deselect">&times;</button>
    </div>

    <div class="insp-edge-flow">
      <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 90px;" title="${esc(srcTitle)}">${esc(srcTitle)}</span>
      <span style="color: var(--mute); font-size: 13px;">&rarr;</span>
      <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 90px;" title="${esc(tgtTitle)}">${esc(tgtTitle)}</span>
    </div>

    <div class="insp-section">
      <div class="insp-section-title">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span>Routing Branch</span>
      </div>
      <div class="insp-field">
        <label>Branch Condition</label>
        <select id="br">
          <option value="">Default (Direct Flow)</option>
          <option value="yes">\u2713 Yes (Qualified)</option>
          <option value="no">\u2715 No (Disqualified)</option>
        </select>
      </div>
      <div class="insp-field">
        <label>Edge Label Badge</label>
        <input type="text" id="lb" value="${esc(currText)}" placeholder="e.g. Yes (qualified)">
      </div>
    </div>

    <div class="insp-section" style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--line);">
      <button class="btn-delete-node" id="del">
        ${ICONS.trash}
        <span>Delete Connection</span>
      </button>
    </div>
  `;

  $('br').value = (m.properties || {}).branch || '';
  $('br').onchange = (e) => {
    const v = e.target.value, t = v === 'yes' ? '\u2713 Yes (qualified)' : v === 'no' ? '\u2715 No (disqualified)' : '';
    lf.setProperties(id, { branch: v }); lf.updateText(id, t); $('lb').value = t; queueSave();
  };
  $('lb').oninput = (e) => { lf.updateText(id, e.target.value); queueSave(); };
  $('insp-close').onclick = () => { emptyInspector(); };
  $('del').onclick = () => { lf.deleteEdge(id); emptyInspector(); queueSave(); };
}
lf.on('node:click', (a) => showNode(a.data.id));
lf.on('edge:click', (a) => showEdge(a.data.id));
lf.on('blank:click', emptyInspector);
lf.on('node:dnd-add', (a) => {
  const isTrig = a.data.type === 'trigger' || a.data.type === 'circle';
  if (isTrig) {
    const existing = lf.getGraphData().nodes.filter((n) => (n.type === 'trigger' || n.type === 'circle') && n.id !== a.data.id);
    if (existing.length) {
      existing.forEach((old) => lf.deleteNode(old.id));
      say('Replaced previous trigger with the new one.');
    } else {
      say('Trigger added.');
    }
  } else {
    say('Node added. Connect it by dragging from one of its dots.');
  }
  showNode(a.data.id);
  queueSave();
});
lf.on('connection:not-allowed', (a) => say(a.msg || 'That connection is not allowed.'));
lf.on('history:change', queueSave);

// First two connections out of a decision become Yes, then No.
lf.on('edge:add', ({ data: d }) => {
  const src = lf.getNodeModelById(d.sourceNodeId); if (!src || (src.type !== 'decision' && src.type !== 'diamond')) return;
  const used = lf.getNodeEdges(src.id).filter((e) => e.sourceNodeId === src.id && e.id !== d.id).map((e) => (e.properties || {}).branch);
  const b = !used.includes('yes') ? 'yes' : !used.includes('no') ? 'no' : '';
  if (b) {
    lf.setProperties(d.id, { branch: b });
    lf.updateText(d.id, b === 'yes' ? '\u2713 Yes (qualified)' : '\u2715 No (disqualified)');
    queueSave();
  }
});

/* ---------- Auto-Layout (Deterministic Hierarchical Topological Alignment) ---------- */
function autoLayoutGraph() {
  const g = lf.getGraphData();
  const nodes = g.nodes || [];
  const edges = g.edges || [];
  if (!nodes.length) {
    say('No nodes to layout.');
    return;
  }

  // 1. Build adjacency list & in-degrees
  const inDegree = new Map();
  const adj = new Map();
  nodes.forEach((n) => {
    inDegree.set(n.id, 0);
    adj.set(n.id, []);
  });
  edges.forEach((e) => {
    if (adj.has(e.sourceNodeId) && inDegree.has(e.targetNodeId)) {
      adj.get(e.sourceNodeId).push(e);
      inDegree.set(e.targetNodeId, (inDegree.get(e.targetNodeId) || 0) + 1);
    }
  });

  // 2. Identify start nodes (Trigger or in-degree 0)
  let roots = nodes.filter((n) => inDegree.get(n.id) === 0);
  if (!roots.length) {
    const trig = nodes.find((n) => n.type === 'trigger' || n.type === 'circle');
    roots = trig ? [trig] : [nodes[0]];
  }

  // 3. Compute topological ranks (longest path layers)
  const ranks = new Map();
  const queue = [...roots];
  roots.forEach((r) => ranks.set(r.id, 0));

  while (queue.length) {
    const curr = queue.shift();
    const currRank = ranks.get(curr.id) || 0;
    const out = adj.get(curr.id) || [];
    for (const e of out) {
      const nextId = e.targetNodeId;
      const existingRank = ranks.get(nextId);
      if (existingRank === undefined || currRank + 1 > existingRank) {
        ranks.set(nextId, currRank + 1);
        const nextNode = nodes.find((n) => n.id === nextId);
        if (nextNode && !queue.includes(nextNode)) {
          queue.push(nextNode);
        }
      }
    }
  }

  // Handle any disconnected / orphan nodes
  nodes.forEach((n) => {
    if (!ranks.has(n.id)) {
      ranks.set(n.id, 0);
    }
  });

  // 4. Group nodes by rank level
  const maxRank = Math.max(...Array.from(ranks.values()), 0);
  const byRank = [];
  for (let r = 0; r <= maxRank; r++) byRank[r] = [];
  nodes.forEach((n) => {
    const r = ranks.get(n.id) || 0;
    byRank[r].push(n);
  });

  // 5. Calculate clean coordinates (Horizontal progression left to right)
  const START_X = 180;
  const DX = 300;
  const BASE_Y = 240;
  const DY = 170;

  const newPos = new Map();

  byRank.forEach((levelNodes, r) => {
    // Keep 'yes' branch upper / straight, and 'no' branch lower
    levelNodes.sort((a, b) => {
      const edgeA = edges.find((e) => e.targetNodeId === a.id);
      const edgeB = edges.find((e) => e.targetNodeId === b.id);
      const brA = (edgeA && edgeA.properties && edgeA.properties.branch) || '';
      const brB = (edgeB && edgeB.properties && edgeB.properties.branch) || '';
      if (brA === 'yes') return -1;
      if (brB === 'yes') return 1;
      return 0;
    });

    const count = levelNodes.length;
    const x = START_X + r * DX;
    levelNodes.forEach((n, idx) => {
      const y = BASE_Y + (idx - (count - 1) / 2) * DY;
      newPos.set(n.id, { x: Math.round(x), y: Math.round(y) });
    });
  });

  // 6. Update nodes & re-route edges with clean anchors
  const updatedNodes = nodes.map((n) => {
    const pos = newPos.get(n.id);
    return pos ? { ...n, x: pos.x, y: pos.y } : n;
  });

  const nodeMap = Object.fromEntries(updatedNodes.map((n) => [n.id, n]));
  const updatedEdges = edges.map((e) => {
    const sNode = nodeMap[e.sourceNodeId];
    const tNode = nodeMap[e.targetNodeId];
    if (!sNode || !tNode) return e;

    const dx = tNode.x - sNode.x;
    const dy = tNode.y - sNode.y;

    let sAnchor = 1; // right
    let tAnchor = 3; // left

    if (Math.abs(dy) > Math.abs(dx) * 1.3) {
      if (dy > 0) {
        sAnchor = 2; // bottom
        tAnchor = 0; // top
      } else {
        sAnchor = 0; // top
        tAnchor = 2; // bottom
      }
    } else if (dx < -30) {
      sAnchor = 2;
      tAnchor = 2;
    }

    return {
      ...e,
      sourceAnchorId: `${sNode.id}_${sAnchor}`,
      targetAnchorId: `${tNode.id}_${tAnchor}`,
      startPoint: anchor(sNode, sAnchor),
      endPoint: anchor(tNode, tAnchor),
    };
  });

  lf.render({ nodes: updatedNodes, edges: updatedEdges });
  lf.fitView(40, 40);
  zoomLabel();
  queueSave();
  say('Auto-Layout complete: Nodes aligned and connectors optimized.');
}

/* ---------- Toolbar ---------- */
const zoomLabel = () => { try { $('zv').textContent = Math.round(lf.getTransform().SCALE_X * 100) + '%'; } catch (_) {} };
lf.on('graph:transform', zoomLabel);
$('zi').onclick = () => { lf.zoom(true); zoomLabel(); };
$('zo').onclick = () => { lf.zoom(false); zoomLabel(); };
$('fit').onclick = () => { lf.fitView(40, 40); zoomLabel(); };
$('undo').onclick = () => lf.undo();
$('redo').onclick = () => lf.redo();
$('reset').onclick = () => {
  if (!confirm('Replace this workflow with the default lead lifecycle?')) return;
  lf.render(defaultGraph()); lf.fitView(40, 40); emptyInspector(); queueSave(); say('Workflow reset to the default lead lifecycle.');
};

// Auto-Layout
const autoLayoutBtn = $('autolayout');
if (autoLayoutBtn) {
  autoLayoutBtn.onclick = () => {
    autoLayoutGraph();
  };
}

// MiniMap Navigator Toggle
let miniMapOpen = false;
const miniMapBtn = $('minimap-toggle');
if (miniMapBtn) {
  miniMapBtn.onclick = () => {
    if (!lf.extension || !lf.extension.miniMap) {
      say('MiniMap extension loading...');
      return;
    }
    miniMapOpen = !miniMapOpen;
    miniMapBtn.classList.toggle('active', miniMapOpen);
    if (miniMapOpen) {
      lf.extension.miniMap.show();
      say('MiniMap opened (navigator active in bottom-right).');
    } else {
      lf.extension.miniMap.hide();
      say('MiniMap closed.');
    }
  };
  lf.on('miniMap:close', () => {
    miniMapOpen = false;
    miniMapBtn.classList.remove('active');
  });
}

// Blueprint Snapshot Export (PNG)
const snapshotBtn = $('snapshot');
if (snapshotBtn) {
  snapshotBtn.onclick = async () => {
    if (!lf.getSnapshot) {
      say('Snapshot extension not ready.');
      return;
    }
    try {
      say('Exporting workflow blueprint diagram...');
      await lf.getSnapshot('workflow-blueprint.png', {
        backgroundColor: '#ffffff',
      });
      say('Blueprint diagram exported as PNG image!');
    } catch (err) {
      console.error('Snapshot error:', err);
      say('Failed to export snapshot.');
    }
  };
}

/* ---------- JSON Export & Preview Modal ---------- */
let currentJsonString = '';

function exportWorkflowJson() {
  const g = clean();
  const exportData = {
    workflow_id: data.id,
    name: data.name,
    exported_at: new Date().toISOString(),
    revision,
    graph: g,
  };
  currentJsonString = JSON.stringify(exportData, null, 2);

  const codeEl = $('wf-json-code');
  if (codeEl) {
    codeEl.textContent = currentJsonString;
  }

  const statsEl = $('wf-json-stats');
  if (statsEl) {
    const nodeCount = (g.nodes || []).length;
    const edgeCount = (g.edges || []).length;
    const bytes = new Blob([currentJsonString]).size;
    const kb = (bytes / 1024).toFixed(1);
    statsEl.textContent = `${nodeCount} nodes · ${edgeCount} connections · ${kb} KB`;
  }

  const modal = $('modal-json-export');
  if (modal) {
    modal.style.display = 'flex';
  }
}

function downloadJsonFile() {
  if (!currentJsonString) {
    const g = clean();
    currentJsonString = JSON.stringify({
      workflow_id: data.id,
      name: data.name,
      exported_at: new Date().toISOString(),
      revision,
      graph: g,
    }, null, 2);
  }
  const blob = new Blob([currentJsonString], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `workflow-${(data.name || 'blueprint').toLowerCase().replace(/[^a-z0-9]+/g, '-')}-r${revision}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  say('Workflow blueprint downloaded as JSON file.');
}

function initModals() {
  // 1. Shortcuts Modal
  const btnShortcuts = $('btn-shortcuts');
  const modalShortcuts = $('modal-shortcuts');
  const closeShortcuts = $('close-shortcuts');
  if (btnShortcuts && modalShortcuts) {
    btnShortcuts.onclick = () => { modalShortcuts.style.display = 'flex'; };
    if (closeShortcuts) closeShortcuts.onclick = () => { modalShortcuts.style.display = 'none'; };
    modalShortcuts.onclick = (e) => { if (e.target === modalShortcuts) modalShortcuts.style.display = 'none'; };
  }

  // 2. Version History Modal & Restore
  const btnHistory = $('btn-history');
  const modalHistory = $('modal-history');
  const closeHistory = $('close-history');
  const btnRevertPublished = $('btn-revert-published');
  if (btnHistory && modalHistory) {
    btnHistory.onclick = () => { modalHistory.style.display = 'flex'; };
    if (closeHistory) closeHistory.onclick = () => { modalHistory.style.display = 'none'; };
    modalHistory.onclick = (e) => { if (e.target === modalHistory) modalHistory.style.display = 'none'; };
    if (btnRevertPublished) {
      btnRevertPublished.onclick = () => {
        if (!data.published_graph) {
          say('No published version available to restore.');
          return;
        }
        if (!confirm(`Restore published production version (v${data.published_version}) into the editor? This will overwrite the current draft.`)) {
          return;
        }
        lf.render(data.published_graph);
        lf.fitView(40, 40);
        emptyInspector();
        queueSave();
        modalHistory.style.display = 'none';
        say(`Restored published production version v${data.published_version} into editor.`);
      };
    }
  }

  // 3. Visual Swimlanes Phase Overlay
  let swimlanesVisible = false;
  const swimlanesBtn = $('swimlanes-toggle');
  const swimlanesEl = $('wf-swimlanes');
  if (swimlanesBtn && swimlanesEl) {
    swimlanesBtn.onclick = () => {
      swimlanesVisible = !swimlanesVisible;
      swimlanesEl.style.display = swimlanesVisible ? 'block' : 'none';
      swimlanesBtn.classList.toggle('active', swimlanesVisible);
      say(swimlanesVisible ? 'Swimlanes enabled: Background phases visible.' : 'Swimlanes hidden.');
    };
  }

  // 4. JSON Export Button
  const exportJsonBtn = $('export-json');
  if (exportJsonBtn) {
    exportJsonBtn.onclick = exportWorkflowJson;
  }

  // 5. JSON Export & Preview Modal
  const modalJsonExport = $('modal-json-export');
  const closeJsonExport = $('close-json-export');
  const btnCancelJson = $('btn-cancel-json');
  const btnCopyJson = $('btn-copy-json');
  const btnDownloadJson = $('btn-download-json');
  const copyLabel = $('copy-json-label');

  if (closeJsonExport && modalJsonExport) {
    closeJsonExport.onclick = () => { modalJsonExport.style.display = 'none'; };
  }
  if (btnCancelJson && modalJsonExport) {
    btnCancelJson.onclick = () => { modalJsonExport.style.display = 'none'; };
  }
  if (modalJsonExport) {
    modalJsonExport.onclick = (e) => {
      if (e.target === modalJsonExport) modalJsonExport.style.display = 'none';
    };
  }

  if (btnCopyJson) {
    btnCopyJson.onclick = async () => {
      if (!currentJsonString) return;
      try {
        await navigator.clipboard.writeText(currentJsonString);
        btnCopyJson.classList.add('copied');
        if (copyLabel) copyLabel.textContent = 'Copied!';
        say('Workflow JSON copied to clipboard!');
        setTimeout(() => {
          btnCopyJson.classList.remove('copied');
          if (copyLabel) copyLabel.textContent = 'Copy JSON';
        }, 2200);
      } catch (err) {
        // Fallback for document.execCommand
        const ta = document.createElement('textarea');
        ta.value = currentJsonString;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btnCopyJson.classList.add('copied');
        if (copyLabel) copyLabel.textContent = 'Copied!';
        say('Workflow JSON copied to clipboard!');
        setTimeout(() => {
          btnCopyJson.classList.remove('copied');
          if (copyLabel) copyLabel.textContent = 'Copy JSON';
        }, 2200);
      }
    };
  }

  if (btnDownloadJson) {
    btnDownloadJson.onclick = () => {
      downloadJsonFile();
      if (modalJsonExport) modalJsonExport.style.display = 'none';
    };
  }

  // 6. JSON Import Button & File Reader
  const importBtn = $('btn-import-json');
  const importInput = $('input-import-json');
  if (importBtn && importInput) {
    importBtn.onclick = () => importInput.click();
    importInput.onchange = (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        try {
          const parsed = JSON.parse(ev.target.result);
          const graphToLoad = parsed.graph || parsed;
          if (!graphToLoad || !Array.isArray(graphToLoad.nodes)) {
            alert('Invalid workflow JSON file: missing "nodes" array.');
            return;
          }
          if (confirm(`Import workflow blueprint "${parsed.name || file.name}" with ${graphToLoad.nodes.length} nodes?`)) {
            lf.render(graphToLoad);
            lf.fitView(40, 40);
            emptyInspector();
            queueSave();
            say(`Successfully imported "${parsed.name || file.name}".`);
          }
        } catch (err) {
          console.error('Import error:', err);
          alert('Failed to parse JSON file: ' + err.message);
        } finally {
          importInput.value = '';
        }
      };
      reader.readAsText(file);
    };
  }

  // Global Keyboard Shortcuts (? for cheat sheet, Escape to close modals)
  window.addEventListener('keydown', (e) => {
    if (e.key === '?' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
      if (modalShortcuts) modalShortcuts.style.display = 'flex';
    } else if (e.key === 'Escape') {
      if (modalShortcuts) modalShortcuts.style.display = 'none';
      if (modalHistory) modalHistory.style.display = 'none';
      if (modalJsonExport) modalJsonExport.style.display = 'none';
    }
  });
}

// Outcome change re-renders live token previews in inspector
const outcomeSel = $('outcome');
if (outcomeSel) {
  outcomeSel.addEventListener('change', () => {
    insp.querySelectorAll('[data-field-wrap]').forEach((wrap) => {
      const key = wrap.dataset.fieldWrap;
      const input = wrap.querySelector(`[data-k="${key}"]`);
      if (input && String(input.value).includes('{{')) {
        const previewEl = $(`preview-${key}`);
        if (previewEl) {
          const body = previewEl.querySelector('.token-live-preview-body');
          if (body) body.textContent = resolveTokens(input.value);
          previewEl.style.display = 'block';
        }
      }
    });
  });
}

/* ---------- Publish ---------- */
$('publish').onclick = async () => {
  if (!(await flush())) { say('Could not save the latest changes.'); return; }
  if (runValidate().length) { say(`Fix ${issues.length} issue(s) before publishing.`); emptyInspector(); return; }
  try {
    const r = await api.publish();
    $('wf-status').textContent = 'Active · v' + r.version; say(`Published version ${r.version}.`);
  } catch (e) {
    if (e.status === 422 && e.body && e.body.errors) { mark(e.body.errors.map((x) => x.node).filter(Boolean), 'bad'); say(e.body.errors[0].message); }
    else say('Publish failed. Try again.');
  }
};

/* ---------- Simulate (Server-side dry run) & last run overlay ---------- */
let busy = false;
const state = (id, cls) => {
  const el = document.querySelector(`[data-nid="${CSS.escape(id)}"]`);
  if (el) {
    el.classList.remove('run', 'done', 'bad');
    if (cls) el.classList.add(cls);
  }
};
const clearMarks = () => document.querySelectorAll('.wf-node-g').forEach((el) => el.classList.remove('run', 'done'));
const nodeTitle = (id) => { const m = lf.getNodeModelById(id); return m ? (m.properties && m.properties.title) : id; };
function edgeBetween(a, b) {
  try {
    return (lf.getNodeEdges(a) || []).find((e) => e.sourceNodeId === a && e.targetNodeId === b);
  } catch (_) {
    return null;
  }
}

$('sim').onclick = async () => {
  if (busy) return; busy = true;
  try {
    if (!(await flush())) { say('Could not save the latest changes.'); return; }
    clearMarks();
    const yes = $('outcome').value === 'yes';
    const sample = yes
      ? { name: 'Sarah Jenkins', email: 'sarah.jenkins@cloudscale.io', company: 'CloudScale Technologies', score: 85, status: 'new', stage: 'new', source: 'web', country: 'US' }
      : { name: 'David Miller', email: 'dave@daveshardware.com', company: "Dave's Hardware", score: 25, status: 'new', stage: 'new', source: 'web', country: 'US' };
    const r = await api.simulate({ sample });
    for (let i = 0; i < r.steps.length; i++) {
      const s = r.steps[i], next = r.steps[i + 1];
      state(s.node_id, 'run');
      say('Simulating: ' + nodeTitle(s.node_id));
      const e = next && edgeBetween(s.node_id, next.node_id);
      if (e) {
        try { lf.openEdgeAnimation(e.id); } catch (_) {}
      }
      await sleep(650);
      state(s.node_id, 'done');
      if (e) {
        try { lf.closeEdgeAnimation(e.id); } catch (_) {}
      }
    }
    say('Simulation finished at: ' + nodeTitle(r.steps[r.steps.length - 1].node_id) + '. Nothing was changed.');
  } catch (e) {
    console.error('Simulation error:', e);
    if (e.status === 422 && e.body && e.body.errors) {
      mark(e.body.errors.map((x) => x.node).filter(Boolean), 'bad');
      say(e.body.errors[0].message);
    } else {
      const msg = (e.body && (e.body.message || (e.body.errors && e.body.errors[0]?.message))) || e.message || 'Simulation failed. Try again.';
      say(msg);
    }
  } finally { busy = false; }
};

$('lastrun').onclick = async () => {
  try {
    const r = await api.latestRun(); clearMarks();
    if (!r.steps.length) { say('No runs yet. Runs appear after a new lead is created.'); return; }
    r.steps.forEach((s) => state(s.node_id, s.status === 'failed' ? 'bad' : 'done'));
    say(`Last run: ${r.status}.` + (r.steps.some((s) => s.error) ? ' ' + r.steps.find((s) => s.error).error : ''));
  } catch (_) { say('Could not load the last run.'); }
};

/* ---------- Boot ---------- */
const hasGraph = data.graph && data.graph.nodes && data.graph.nodes.length;
lf.render(hasGraph ? data.graph : defaultGraph());
emptyInspector();
initModals();
ready = true;
setTimeout(() => { lf.fitView(40, 40); zoomLabel(); runValidate(); }, 60);
if (!hasGraph) queueSave();
if (window.ResizeObserver) new ResizeObserver(() => { const s = $('stage'); try { lf.resize(s.clientWidth, s.clientHeight); } catch (_) {} }).observe($('stage'));
