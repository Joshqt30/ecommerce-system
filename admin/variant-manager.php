<!-- Save as: ../admin/variant-manager.php -->
<?php
/**
 * Variant Manager — include inside any admin product form.
 * Requires: $productId (int, 0 for new product), $pdo (PDO connection)
 * Outputs:  <div id="variantCard"> + hidden input #variantsJson
 *           On form submit, JS populates #variantsJson with all variant data.
 */

// Load existing variants for edit mode
$existingVariants = [];
if (!empty($productId) && $pdo) {
    $vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = :pid ORDER BY id");
    $vStmt->execute([':pid' => $productId]);
    $existingVariants = $vStmt->fetchAll();
}
?>

<div class="card" id="variantCard">
  <div class="card-title">
    Product Variants
    <span style="font-size:11px;font-weight:400;color:var(--muted);margin-left:8px">
      Define options like Color + Storage. Each combination = one variant row.
    </span>
  </div>

  <!-- Step 1: Define attribute types -->
  <div class="form-group">
    <label class="form-label">Attribute Types</label>
    <p class="section-hint">Name the options this product has. e.g. "Color", "Storage", "Size"</p>
    <div id="attrTypeList" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px"></div>
    <div style="display:flex;gap:8px;align-items:center">
      <input class="form-input" type="text" id="newAttrTypeInput"
             placeholder="e.g. Storage" style="max-width:200px"/>
      <button type="button" class="btn-secondary" style="white-space:nowrap"
              onclick="addAttrType()">+ Add Type</button>
    </div>
  </div>

  <!-- Step 2: Define values per type -->
  <div id="attrValuesSection" style="display:none">
    <div class="form-group">
      <label class="form-label">Attribute Values</label>
      <p class="section-hint">Add the options for each type.</p>
      <div id="attrValuesList"></div>
    </div>
    <button type="button" class="btn-secondary" onclick="generateVariants()" style="margin-bottom:16px">
      ⚡ Generate Variant Combinations
    </button>
  </div>

  <!-- Step 3: Variant table -->
  <div id="variantTableWrap" style="display:none">
    <label class="form-label">Variant Details</label>
    <p class="section-hint">Set price and stock for each combination. Leave price blank to inherit the base price.</p>
    <div style="overflow-x:auto">
      <table id="variantTable" style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="border-bottom:2px solid var(--border)">
            <th id="variantAttrHeaders" style="text-align:left;padding:8px 10px;color:var(--muted);font-weight:600"></th>
            <th style="text-align:left;padding:8px 10px;color:var(--muted);font-weight:600;min-width:120px">Price (₱)</th>
            <th style="text-align:left;padding:8px 10px;color:var(--muted);font-weight:600;min-width:80px">Stock</th>
            <th style="text-align:left;padding:8px 10px;color:var(--muted);font-weight:600;min-width:120px">SKU</th>
            <th style="text-align:left;padding:8px 10px;color:var(--muted);font-weight:600;min-width:80px">Image</th>
            <th style="padding:8px 10px"></th>
          </tr>
        </thead>
        <tbody id="variantTableBody"></tbody>
      </table>
    </div>
  </div>

  <input type="hidden" name="variants_json" id="variantsJson"/>
</div>

<script>
// ── Variant Manager State ──────────────────────────────
let attrTypes  = [];   // ['Color', 'Storage']
let attrValues = {};   // { Color: ['Midnight','Starlight'], Storage: ['128GB','256GB'] }
let variants   = [];   // [{ attrs:{Color:'Midnight',Storage:'128GB'}, price:'', stock:'', sku:'', imageFile:null, existingId:null }]

// ── Load existing variants (edit mode) ─────────────────
const existingVariants = <?= json_encode($existingVariants) ?>;

if (existingVariants.length > 0) {
  // Reconstruct attrTypes and attrValues from existing data
  existingVariants.forEach(v => {
    const attrs = typeof v.attributes === 'string' ? JSON.parse(v.attributes) : v.attributes;
    Object.entries(attrs).forEach(([k, val]) => {
      if (!attrTypes.includes(k)) attrTypes.push(k);
      if (!attrValues[k]) attrValues[k] = [];
      if (!attrValues[k].includes(val)) attrValues[k].push(val);
    });
  });
  renderAttrTypes();
  renderAttrValues();
  // Build variant rows from existing data
  variants = existingVariants.map(v => ({
    attrs: typeof v.attributes === 'string' ? JSON.parse(v.attributes) : v.attributes,
    price: v.price || '',
    stock: v.stock_quantity ?? '',
    sku:   v.sku   || '',
    imageFile: null,
    existingImageUrl: v.image_url || '',
    existingId: v.id,
  }));
  renderVariantTable();
  document.getElementById('variantTableWrap').style.display = 'block';
}

// ── Attribute Type Management ──────────────────────────
function addAttrType() {
  const input = document.getElementById('newAttrTypeInput');
  const val   = input.value.trim();
  if (!val || attrTypes.includes(val)) return;
  attrTypes.push(val);
  attrValues[val] = [];
  input.value = '';
  renderAttrTypes();
  renderAttrValues();
  document.getElementById('attrValuesSection').style.display = 'block';
}

function removeAttrType(type) {
  attrTypes = attrTypes.filter(t => t !== type);
  delete attrValues[type];
  renderAttrTypes();
  renderAttrValues();
  if (attrTypes.length === 0)
    document.getElementById('attrValuesSection').style.display = 'none';
}

function renderAttrTypes() {
  const list = document.getElementById('attrTypeList');
  list.innerHTML = '';
  attrTypes.forEach(t => {
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.style.cssText = 'font-size:13px;padding:5px 12px;background:#f0f4ff;color:#2563eb;border-radius:20px;display:flex;align-items:center;gap:6px';
    chip.innerHTML = `${t} <button type="button" onclick="removeAttrType('${t}')" style="background:none;border:none;cursor:pointer;font-size:14px;color:#2563eb">×</button>`;
    list.appendChild(chip);
  });
}

// ── Attribute Value Management ─────────────────────────
function addAttrValue(type) {
  const input = document.getElementById('attrValInput_' + type);
  const val   = input.value.trim();
  if (!val || attrValues[type].includes(val)) return;
  attrValues[type].push(val);
  input.value = '';
  renderAttrValues();
}

function removeAttrValue(type, val) {
  attrValues[type] = attrValues[type].filter(v => v !== val);
  renderAttrValues();
}

function renderAttrValues() {
  const container = document.getElementById('attrValuesList');
  container.innerHTML = '';
  attrTypes.forEach(type => {
    const div = document.createElement('div');
    div.style.cssText = 'margin-bottom:14px;padding:14px;background:#fafafa;border-radius:10px;border:1.5px solid var(--border)';
    const chips = (attrValues[type] || []).map(v =>
      `<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:#e0f2fe;color:#0369a1;border-radius:20px;font-size:11px;font-weight:600;margin:2px">
        ${v}
        <button type="button" onclick="removeAttrValue('${type}','${v}')" style="background:none;border:none;cursor:pointer;color:#0369a1;font-size:13px;line-height:1">×</button>
      </span>`
    ).join('');
    div.innerHTML = `
      <div style="font-size:12px;font-weight:700;margin-bottom:8px">${type}</div>
      <div style="margin-bottom:8px">${chips || '<span style="color:var(--muted);font-size:12px">No values yet</span>'}</div>
      <div style="display:flex;gap:8px">
        <input class="form-input" type="text" id="attrValInput_${type}"
               placeholder="Add a ${type} value" style="max-width:180px"
               onkeydown="if(event.key==='Enter'){event.preventDefault();addAttrValue('${type}')}"/>
        <button type="button" class="btn-secondary" onclick="addAttrValue('${type}')">+ Add</button>
      </div>`;
    container.appendChild(div);
  });
}

// ── Generate Combinations (Cartesian product) ──────────
function generateVariants() {
  const valueArrays = attrTypes.map(t => attrValues[t] || []);
  if (valueArrays.some(a => a.length === 0)) {
    alert('Please add at least one value for each attribute type.');
    return;
  }
  // Cartesian product
  const combos = valueArrays.reduce((acc, arr) =>
    acc.flatMap(combo => arr.map(val => [...combo, val])), [[]]
  );
  // Merge with existing variants (preserve price/stock if same attrs combo exists)
  variants = combos.map(combo => {
    const attrs = {};
    attrTypes.forEach((t, i) => attrs[t] = combo[i]);
    const key = JSON.stringify(attrs);
    const existing = variants.find(v => JSON.stringify(v.attrs) === key);
    return existing || { attrs, price: '', stock: '', sku: '', imageFile: null, existingImageUrl: '', existingId: null };
  });
  renderVariantTable();
  document.getElementById('variantTableWrap').style.display = 'block';
}

// ── Render Variant Table ───────────────────────────────
function renderVariantTable() {
  // Headers
  document.getElementById('variantAttrHeaders').textContent = attrTypes.join(' / ');

  const tbody = document.getElementById('variantTableBody');
  tbody.innerHTML = '';
  variants.forEach((v, i) => {
    const attrLabel = attrTypes.map(t => v.attrs[t]).join(' / ');
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid var(--border)';
    tr.innerHTML = `
      <td style="padding:10px">${attrLabel}</td>
      <td style="padding:10px">
        <div class="input-prefix" style="max-width:130px">
          <span class="input-prefix-label">₱</span>
          <input type="number" step="0.01" placeholder="Base price"
                 value="${v.price}"
                 oninput="variants[${i}].price = this.value"
                 style="border:none;background:transparent;padding:8px 10px;font-family:var(--font);font-size:13px;outline:none;width:100%"/>
        </div>
      </td>
      <td style="padding:10px">
        <input class="form-input" type="number" min="0" placeholder="0"
               value="${v.stock}"
               oninput="variants[${i}].stock = this.value"
               style="max-width:80px"/>
      </td>
      <td style="padding:10px">
        <input class="form-input" type="text" placeholder="SKU-001"
               value="${v.sku}"
               oninput="variants[${i}].sku = this.value"
               style="max-width:130px"/>
      </td>
      <td style="padding:10px">
        <label style="cursor:pointer;font-size:12px;color:var(--blue);font-weight:600">
          ${v.existingImageUrl ? '✓ Image' : '+ Image'}
          <input type="file" name="variant_image_${i}" accept="image/*" style="display:none"
       onchange="handleVariantImage(${i}, this)"/>
        </label>
        ${v.existingId ? `<input type="hidden" name="variant_existing_id[]" value="${v.existingId}"/>` : ''}
      </td>
      <td style="padding:10px">
        <button type="button" onclick="removeVariant(${i})"
                style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:18px;line-height:1" title="Remove">×</button>
      </td>`;
    tbody.appendChild(tr);
  });
}

function removeVariant(i) {
  variants.splice(i, 1);
  renderVariantTable();
}

function handleVariantImage(i, input) {
  if (input.files[0]) variants[i].imageFile = input.files[0];
}

// ── Sync variants to hidden JSON before submit ─────────
// Call this from the form's submit handler
function syncVariantsJson() {
  const data = variants.map(v => ({
    existing_id: v.existingId,
    attributes:  v.attrs,
    price:       v.price || null,
    stock:       parseInt(v.stock) || 0,
    sku:         v.sku  || null,
    existing_image_url: v.existingImageUrl || null,
  }));
  document.getElementById('variantsJson').value = JSON.stringify(data);
}
</script>