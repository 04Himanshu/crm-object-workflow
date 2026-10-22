// Single source of truth for node types in the browser. Keep in sync with config/workflows.php.
export const W = 240, H = 96;

export const TYPES = {
  trigger:    { kind: 'Trigger',  category: 'Start Event',       title: 'Lead Inbound Created',          desc: 'Captures incoming lead from Web-to-Lead, API or Ads.', shape: 'circle' },
  assign:     { kind: 'Action',   category: 'Territory Routing', title: 'Route SDR Assignment',         desc: 'Routes lead to SDR queue via round-robin rules.', shape: 'rect' },
  activity:   { kind: 'Action',   category: 'Task & Outreach',   title: 'Schedule Discovery Call',      desc: 'Logs priority 15-min discovery outreach on timeline.', shape: 'rect' },
  stage:      { kind: 'Action',   category: 'Record Update',     title: 'Update Stage: Contacted',      desc: 'Advances lead through Salesforce lifecycle stages (New, Contacted, Working, Qualified, Converted).', shape: 'rect' },
  decision:   { kind: 'Decision', category: 'Decision Gateway',  title: 'Evaluate MQL Criteria',        desc: 'Validates BANT criteria and Einstein Score \u2265 60.', shape: 'diamond' },
  convert:    { kind: 'Action',   category: 'Lead Conversion',   title: 'Convert Qualified Lead',       desc: 'Locks lead record and marks status as Converted.', shape: 'rect' },
  account:    { kind: 'Action',   category: 'CRM Record Sync',   title: 'Create Deal & Account',        desc: 'Generates Account, B2B Contact and Opportunity.', shape: 'rect' },
  disqualify: { kind: 'Action',   category: 'Status Update',     title: 'Disqualify Unfit Lead',        desc: 'Sets Closed - Disqualified with audited reason.', shape: 'rect' },
  archive:    { kind: 'Action',   category: 'Data Retention',    title: 'Route to Nurture Archive',     desc: 'Moves to cold nurture queue with 12-month retention.', shape: 'rect' },
  sticky_note:{ kind: 'Annotation', category: 'Documentation',   title: 'Sticky Note',                  desc: 'Add inline documentation or notes to explain workflow logic.', shape: 'sticky' },
};

export const ACTION_COLORS = {
  trigger:    '#7c3aed',
  assign:     '#2563eb',
  activity:   '#0d9488',
  stage:      '#0891b2',
  decision:   '#d97706',
  convert:    '#16a34a',
  account:    '#059669',
  disqualify: '#dc2626',
  archive:    '#64748b',
  sticky_note:'#eab308',
};

export const GROUPS = [
  ['Trigger', ['trigger']],
  ['Actions', ['assign', 'activity', 'stage', 'convert', 'account', 'disqualify', 'archive']],
  ['Logic', ['decision']],
  ['Annotations', ['sticky_note']],
];

export const DEFAULT_CONFIG = {
  trigger: {},
  assign: { strategy: 'round_robin', owner_id: '' },
  activity: { activity_type: 'call', subject: 'Initial Discovery Call (SLA 15m)' },
  stage: { stage: 'contacted' },
  decision: { field: 'score', op: '>=', value: '60' },
  convert: {},
  account: { create_contact: '1' },
  disqualify: { reason: 'not_a_fit' },
  archive: { retention_months: 12 },
  sticky_note: { note: 'Add architectural comments or instructions here.', color: '#fef08a' },
};

const REASONS = [
  ['not_a_fit', 'Not a Fit / No Requirement'],
  ['no_budget', 'No Budget / Authority'],
  ['no_response', 'Unresponsive (5+ Touches)'],
  ['duplicate', 'Duplicate Lead Record'],
];

// Fields shown in the inspector for each node type.
export const SCHEMA = {
  assign: [
    { key: 'strategy', label: 'Assignment Rule', type: 'select', options: [['round_robin', 'Territory Round-Robin (SDR Pool)'], ['owner', 'Specific Sales Rep']] },
    { key: 'owner_id', label: 'User ID (when specific)', type: 'number' },
  ],
  activity: [
    { key: 'activity_type', label: 'Outreach Channel', type: 'select', options: [['call', 'Phone Call (Discovery)'], ['email', 'Email (Sales Sequence)'], ['meeting', 'Meeting (Product Demo)'], ['note', 'Internal Note']] },
    { key: 'subject', label: 'Activity Subject', type: 'text' },
  ],
  stage: [{ key: 'stage', label: 'Salesforce Lead Stage', type: 'select', options: [
    ['new', '1. New (Lead Inbound Created)'],
    ['contacted', '2. Contacted (Outreach Initiated)'],
    ['working', '3. Working (Requirements Discovery & Follow-up)'],
    ['qualified', '4. Qualified (Meets BANT / Sales Ready)'],
    ['converted', '5. Converted (Lead Converted Milestone)'],
    ['nurturing', 'Branch · Nurturing (Long-Term Drip)'],
    ['unqualified', 'Branch · Unqualified (Does Not Meet ICP)'],
    ['disqualified', 'Branch · Disqualified (Rejected on Business Criteria)'],
  ] }],
  decision: [
    { key: 'field', label: 'CRM Lead Field', type: 'select', options: [
      ['score', 'Lead Score (0-100 \u00B7 Einstein / Fit)'],
      ['country', 'Country / Territory'],
      ['source', 'Lead Source (Web, Inbound, Partner)'],
      ['company', 'Company / Account Name'],
      ['stage', 'Lead Stage'],
    ] },
    { key: 'op', label: 'Evaluation Operator', type: 'select', options: [['>=', '\u2265 (Greater or equal)'], ['>', '> (Greater than)'], ['<=', '\u2264 (Less or equal)'], ['<', '< (Less than)'], ['=', '= (Equals)'], ['!=', '\u2260 (Not equals)'], ['contains', 'contains']] },
    { key: 'value', label: 'Comparison Threshold', type: 'text' },
  ],
  account: [{ key: 'create_contact', label: 'Also Create B2B Contact', type: 'select', options: [['1', 'Yes (Account + Contact + Opportunity)'], ['0', 'No (Account + Opportunity only)']] }],
  disqualify: [{ key: 'reason', label: 'Disqualification Reason', type: 'select', options: REASONS }],
  archive: [{ key: 'retention_months', label: 'Nurture Retention Period (months)', type: 'number' }],
  sticky_note: [
    { key: 'note', label: 'Note Text / Annotation', type: 'textarea' },
    { key: 'color', label: 'Sticky Color', type: 'select', options: [
      ['#fef08a', 'Yellow Note'],
      ['#bfdbfe', 'Blue Note'],
      ['#bbf7d0', 'Green Note'],
      ['#fbcfe8', 'Pink Note'],
      ['#e2e8f0', 'Slate Note'],
    ] },
  ],
};

// The small tag on each card is derived from config, so it can never drift from what the server runs.
export function metaOf(type, c = {}) {
  switch (type) {
    case 'trigger': return 'Event: Lead.Created';
    case 'assign': return c.strategy === 'owner' ? `Owner: user ${c.owner_id || '?'}` : 'Owner: round-robin (SDR)';
    case 'activity': return `Type: ${c.activity_type || 'call'} \u00B7 15m SLA`;
    case 'stage': {
      const stageLabels = {
        new: '1. New',
        contacted: '2. Contacted',
        working: '3. Working',
        qualified: '4. Qualified',
        converted: '5. Converted',
        nurturing: 'Nurturing',
        unqualified: 'Unqualified',
        disqualified: 'Disqualified',
      };
      return `Stage: ${stageLabels[c.stage] || c.stage || 'contacted'}`;
    }
    case 'decision': return `Rule: ${c.field || 'score'} ${c.op === '>=' ? '\u2265' : c.op === '<=' ? '\u2264' : c.op || '='} ${c.value ?? '60'}`;
    case 'convert': return 'Status: converted (MQL)';
    case 'account': return c.create_contact === '0' ? 'Account + Deal' : 'Account, Contact, Deal';
    case 'disqualify': {
      const match = REASONS.find((r) => r[0] === c.reason);
      return `Reason: ${match ? match[1].split('/')[0].trim() : c.reason}`;
    }
    case 'archive': return `Nurture \u00B7 Keep ${c.retention_months || 12} mos`;
    case 'sticky_note': return 'Annotation Note';
    default: return '';
  }
}

export function newProps(type) {
  const config = { ...DEFAULT_CONFIG[type] };
  return {
    title: (TYPES[type] && TYPES[type].title) || type,
    desc: (TYPES[type] && TYPES[type].desc) || '',
    meta: metaOf(type, config),
    action: (TYPES[type] && TYPES[type].kind === 'Action') ? type : '',
    config,
  };
}

export const anchor = (n, i) => {
  const shape = (TYPES[n.type] && TYPES[n.type].shape) || (n.type === 'trigger' || n.type === 'circle' ? 'circle' : n.type === 'decision' || n.type === 'diamond' ? 'diamond' : 'rect');
  if (shape === 'circle') {
    const r = 48;
    return i === 0 ? { x: n.x, y: n.y - r } : i === 1 ? { x: n.x + r, y: n.y } : i === 2 ? { x: n.x, y: n.y + r } : { x: n.x - r, y: n.y };
  }
  if (shape === 'diamond') {
    const rx = 120, ry = 75;
    return i === 0 ? { x: n.x, y: n.y - ry } : i === 1 ? { x: n.x + rx, y: n.y } : i === 2 ? { x: n.x, y: n.y + ry } : { x: n.x - rx, y: n.y };
  }
  // rect
  const hw = 120, hh = 48;
  return i === 0 ? { x: n.x, y: n.y - hh } : i === 1 ? { x: n.x + hw, y: n.y } : i === 2 ? { x: n.x, y: n.y + hh } : { x: n.x - hw, y: n.y };
};

export function defaultGraph() {
  const mk = (id, type, x, y, custom = {}) => {
    const base = newProps(type);
    const config = { ...base.config, ...(custom.config || {}) };
    return {
      id,
      type,
      x,
      y,
      properties: {
        ...base,
        ...custom,
        config,
        meta: custom.meta || metaOf(type, config),
      },
    };
  };

  const nodes = [
    // Stage 1: New & Ingestion
    mk('n1', 'trigger', 180, 160, {
      title: '1. Lead Created (New)',
      desc: 'Inbound lead captured from Web-to-Lead, API or Ads.',
      meta: 'Stage: 1. New',
    }),
    mk('n2', 'assign', 480, 160, {
      title: 'Route SDR Assignment',
      desc: 'Routes lead to SDR queue via territory round-robin rules.',
      meta: 'Owner: round-robin (SDR)',
    }),
    mk('n3', 'activity', 780, 160, {
      title: 'Initial Outreach Call',
      desc: 'Salesperson initiates priority discovery outreach call or email.',
      meta: 'Type: call · 15m SLA',
      config: { activity_type: 'call', subject: 'Initial Discovery Call (SLA 15m)' },
    }),
    // Stage 2: Contacted
    mk('n4', 'stage', 1080, 160, {
      title: '2. Set Stage: Contacted',
      desc: 'Salesperson calls or emails the lead.',
      meta: 'Stage: 2. Contacted',
      config: { stage: 'contacted' },
    }),

    // Stage 3: Working & Requirements Discovery
    mk('n5', 'activity', 1080, 380, {
      title: 'Requirements Discovery',
      desc: 'Sales team follows up and gathers requirements.',
      meta: 'Type: meeting · Discovery',
      config: { activity_type: 'meeting', subject: 'Requirements Gathering & BANT Discovery' },
    }),
    mk('n6', 'stage', 780, 380, {
      title: '3. Set Stage: Working',
      desc: 'Sales team actively works the lead and qualifies criteria.',
      meta: 'Stage: 3. Working',
      config: { stage: 'working' },
    }),

    // Decision Gateway: MQL BANT Criteria
    mk('n7', 'decision', 480, 380, {
      title: 'Evaluate MQL Criteria',
      desc: 'Validates BANT criteria and buying potential (Score ≥ 60).',
      meta: 'Rule: score ≥ 60',
      config: { field: 'score', op: '>=', value: '60' },
    }),

    // Stage 4: Qualified & Stage 5: Converted (Branch YES)
    mk('n8', 'stage', 180, 380, {
      title: '4. Set Stage: Qualified',
      desc: 'Lead meets criteria and has genuine buying potential.',
      meta: 'Stage: 4. Qualified',
      config: { stage: 'qualified' },
    }),
    mk('n9', 'convert', 180, 580, {
      title: '5. Convert Qualified Lead',
      desc: 'Lead becomes an Account and Contact, with an optional Opportunity.',
      meta: 'Status: converted (MQL)',
    }),
    mk('n10', 'account', 180, 760, {
      title: 'Create Deal & Account',
      desc: 'Generates B2B Account, Contact, and Opportunity in CRM.',
      meta: 'Account, Contact, Deal',
      config: { create_contact: '1' },
    }),

    // Disqualification & Nurturing (Branch NO)
    mk('n11', 'stage', 780, 580, {
      title: 'Set Stage: Disqualified',
      desc: 'Rejected based on business criteria or unfit.',
      meta: 'Stage: Disqualified',
      config: { stage: 'disqualified' },
    }),
    mk('n12', 'disqualify', 1080, 580, {
      title: 'Disqualify Unfit Lead',
      desc: 'Closed - Disqualified with audited reason code.',
      meta: 'Reason: Not a Fit',
      config: { reason: 'not_a_fit' },
    }),
    mk('n13', 'archive', 1080, 760, {
      title: 'Route to Nurture Archive',
      desc: 'Moves to cold nurture queue; follow up later (12 mo retention).',
      meta: 'Nurture · Keep 12 mos',
      config: { retention_months: 12 },
    }),
  ];

  const by = Object.fromEntries(nodes.map((n) => [n.id, n]));
  const e = (id, a, ai, b, bi, branch = '', text = '') => {
    const s = anchor(by[a], ai), t = anchor(by[b], bi);
    return {
      id,
      type: 'polyline',
      sourceNodeId: a,
      targetNodeId: b,
      sourceAnchorId: `${a}_${ai}`,
      targetAnchorId: `${b}_${bi}`,
      startPoint: s,
      endPoint: t,
      properties: { branch },
      ...(text ? { text: { value: text } } : {}),
    };
  };

  return {
    nodes,
    edges: [
      // Stage 1 -> SDR -> Outreach -> Stage 2: Contacted
      e('e1', 'n1', 1, 'n2', 3),
      e('e2', 'n2', 1, 'n3', 3),
      e('e3', 'n3', 1, 'n4', 3),

      // Stage 2: Contacted -> Discovery -> Stage 3: Working -> Evaluation Decision
      e('e4', 'n4', 2, 'n5', 0),
      e('e5', 'n5', 3, 'n6', 1),
      e('e6', 'n6', 3, 'n7', 1),

      // Branch YES -> Stage 4: Qualified -> Stage 5: Converted -> Create Account & Deal
      e('e7', 'n7', 3, 'n8', 1, 'yes', '✓ Yes (qualified)'),
      e('e8', 'n8', 2, 'n9', 0),
      e('e9', 'n9', 2, 'n10', 0),

      // Branch NO -> Set Disqualified -> Disqualify Reason -> Nurture Archive
      e('e10', 'n7', 2, 'n11', 0, 'no', '✕ No (disqualified)'),
      e('e11', 'n11', 1, 'n12', 3),
      e('e12', 'n12', 2, 'n13', 0),
    ],
  };
}
