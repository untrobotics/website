<?php
require("../template/top.php");
require_once(BASE . '/dyndns/api/namecom.php');

if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$notice = null;
$new_key = null; // set after creating a key so we can surface it once

$superdomain = DYNDNS_ALLOWED_SUPERDOMAINS[0]; // one allowed superdomain today
$suffix = DYNDNS_FORCE_SUBDOMAIN;              // sub_domains must end in this ("dyndns")

/** Parse the restrictions textarea into a clean list; empty => unrestricted (null). */
function parse_restrictions($raw) {
    $parts = preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
    $parts = array_values(array_unique(array_map('strtolower', array_map('trim', $parts))));
    return $parts ? $parts : null;
}

/** A Name.com v4 client bound to the configured creds, or null if unconfigured. */
function namecom_client() {
    if (!NAMECOM_API_USERNAME || !NAMECOM_API_KEY) {
        return null;
    }
    $nc = new NameComApi();
    $nc->login(NAMECOM_API_USERNAME, NAMECOM_API_KEY);
    return $nc;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $notice = array('err', 'Session expired. Please retry.');
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'create_key') {
            $desc = trim((string) @$_POST['description']);
            $restrictions = parse_restrictions(@$_POST['restrictions']);
            $key = bin2hex(random_bytes(16)); // 32 hex chars, fits varchar(40)
            $restr_val = $restrictions === null ? null : serialize($restrictions);

            $st = $db->prepare('INSERT INTO dyndns_api_keys (api_key_value, description, subdomain_restrictions) VALUES (?, ?, ?)');
            $st->bind_param('sss', $key, $desc, $restr_val);
            if ($st->execute()) {
                $new_key = $key;
                $notice = array('ok', 'Created a new dyndns key. Copy it now — the update URL below includes it.');
            } else {
                $notice = array('err', 'Could not create key: ' . htmlspecialchars($st->error));
            }

        } elseif ($action === 'delete_key') {
            $id = (int) @$_POST['id'];
            $st = $db->prepare('DELETE FROM dyndns_api_keys WHERE id = ?');
            $st->bind_param('i', $id);
            $notice = $st->execute()
                ? array('ok', 'Deleted key #' . $id . '. Any client still using it will stop updating.')
                : array('err', 'Could not delete key: ' . htmlspecialchars($st->error));

        } elseif ($action === 'delete_record') {
            $rid = (int) @$_POST['record_id'];
            $nc = namecom_client();
            if (!$nc) {
                $notice = array('err', 'Name.com is not configured in this environment.');
            } else {
                // Only allow deleting records under the dyndns suffix — never an
                // arbitrary zone record — so re-fetch and verify the host first.
                $records = $nc->list_records($superdomain);
                $target = null;
                if ($records !== null) {
                    foreach ($records as $r) {
                        if ((int) $r->id === $rid) { $target = $r; break; }
                    }
                }
                if (!$target) {
                    $notice = array('err', 'Record not found.');
                } elseif (!isset($target->host) || !preg_match('/\.' . preg_quote($suffix, '/') . '$/i', $target->host)) {
                    $notice = array('err', 'Refusing to delete a record outside *.' . $suffix . '.');
                } elseif ($nc->delete_record($superdomain, $rid)) {
                    $notice = array('ok', 'Deleted DNS record ' . htmlspecialchars($target->fqdn) . '.');
                } else {
                    $notice = array('err', 'Name.com returned HTTP ' . $nc->last_status . ' deleting the record.');
                }
            }
        }
    }
}

// ---- gather data for display ------------------------------------------------
$keys = array();
$kq = $db->query('SELECT k.id, k.api_key_value, k.description, k.subdomain_restrictions, k.uid, u.name AS owner_name
                  FROM dyndns_api_keys k LEFT JOIN users u ON u.id = k.uid ORDER BY k.id ASC');
if ($kq) { while ($row = $kq->fetch_assoc()) { $keys[] = $row; } }

$nc = namecom_client();
$dns_records = null;   // null = couldn't reach Name.com
$dns_error = null;
if ($nc) {
    $all = $nc->list_records($superdomain);
    if ($all === null) {
        $dns_error = 'Name.com returned HTTP ' . $nc->last_status . '.';
    } else {
        $dns_records = array();
        foreach ($all as $r) {
            if (isset($r->host) && preg_match('/\.' . preg_quote($suffix, '/') . '$/i', $r->host)) {
                $dns_records[] = $r;
            }
        }
    }
} else {
    $dns_error = 'Name.com is not configured in this environment (dev has no credentials by design).';
}

head('Dyndns', 'Dyndns');
require_once(BASE . '/admin/_styles.php');
?>
<main id="main-content" class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Dynamic DNS</h1>
                    <p class="lead">Issue and revoke keys members use to point a <code>*.<?php echo htmlspecialchars($suffix . '.' . $superdomain); ?></code> subdomain at their changing home IP, and manage the live records.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="admin-notice <?php echo $notice[0] === 'ok' ? 'ok' : 'err'; ?>"><?php echo $notice[1]; ?></div>
                <?php endif; ?>

                <div class="admin-stats">
                    <?php echo admin_stat(count($keys), 'API keys'); ?>
                    <?php echo admin_stat($dns_records === null ? '—' : count($dns_records), 'Active records', $dns_records === null ? 'grey' : ''); ?>
                </div>

                <div class="admin-help">
                    <strong>How members use a key</strong>
                    <p style="margin:6px 0 0;">Their client (router DDNS, cron, or a ddclient-style script) requests, whenever their IP changes:</p>
                    <ul>
                        <li><code>/dyndns/api/ip2host.php?API_KEY=&lt;key&gt;&amp;super_domain=<?php echo htmlspecialchars($superdomain); ?>&amp;sub_domain=&lt;label&gt;.<?php echo htmlspecialchars($suffix); ?></code></li>
                        <li>The <code>sub_domain</code> must end in <code>.<?php echo htmlspecialchars($suffix); ?></code>. Omit <code>ip</code> to use the caller's address, or pass <code>&amp;ip=1.2.3.4</code> explicitly.</li>
                        <li>Restrict a key to specific sub_domains so it can only touch its own name; leave blank for unrestricted.</li>
                    </ul>
                </div>

                <?php if ($new_key): ?>
                    <div class="admin-card">
                        <div class="hd">New key — copy it now</div>
                        <div class="bd">
                            <p style="margin:0 0 8px;">Give the member this update URL (fill in their sub_domain):</p>
                            <textarea class="admin-form" readonly rows="2" style="width:100%;font-family:monospace;font-size:12.5px;padding:9px 11px;border:1px solid #d6dbd8;border-radius:8px;">https://<?php echo htmlspecialchars($superdomain); ?>/dyndns/api/ip2host.php?API_KEY=<?php echo htmlspecialchars($new_key); ?>&super_domain=<?php echo htmlspecialchars($superdomain); ?>&sub_domain=THEIRNAME.<?php echo htmlspecialchars($suffix); ?></textarea>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="hd">Issue a new key</div>
                    <div class="bd">
                        <form method="post" action="/admin/dyndns" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="action" value="create_key">
                            <div class="fld">
                                <label for="description">Description</label>
                                <input id="description" type="text" name="description" maxlength="255" placeholder="e.g. Alice's home lab">
                            </div>
                            <div class="fld">
                                <label for="restrictions">Restrict to sub_domains <span style="font-weight:400;color:#8a908c;">(optional, comma or space separated; blank = unrestricted)</span></label>
                                <input id="restrictions" type="text" name="restrictions" placeholder="alice.<?php echo htmlspecialchars($suffix); ?>">
                            </div>
                            <button class="btn-solid go">Generate key</button>
                        </form>
                    </div>
                </div>

                <div class="admin-section-title">Keys</div>
                <div class="admin-card">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>#</th><th>Owner</th><th>Description</th><th>Key</th><th>Restrictions</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if ($keys): foreach ($keys as $k):
                                $restr = @unserialize((string) $k['subdomain_restrictions']);
                            ?>
                                <tr>
                                    <td class="num"><?php echo (int) $k['id']; ?></td>
                                    <td><?php echo $k['uid'] === null
                                            ? '<span class="pill pill-neutral">Global</span>'
                                            : ($k['owner_name'] !== null ? htmlspecialchars($k['owner_name']) : ('<span class="muted">uid ' . (int) $k['uid'] . '</span>')); ?></td>
                                    <td><?php echo $k['description'] !== null && $k['description'] !== '' ? htmlspecialchars($k['description']) : '<span class="muted">—</span>'; ?></td>
                                    <td><code style="font-size:12.5px;"><?php echo htmlspecialchars($k['api_key_value']); ?></code></td>
                                    <td><?php echo (is_array($restr) && $restr) ? htmlspecialchars(implode(', ', $restr)) : '<span class="muted">Unrestricted</span>'; ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <form method="post" action="/admin/dyndns" onsubmit="return confirm('Delete key #<?php echo (int) $k['id']; ?>? Clients using it will stop updating.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="delete_key">
                                                <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
                                                <button class="btn-pill danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="6" class="admin-empty">No keys yet. Issue one above.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-section-title">Live records <span style="font-weight:400;color:#8a908c;font-size:13px;">*.<?php echo htmlspecialchars($suffix . '.' . $superdomain); ?></span></div>
                <div class="admin-card">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Host</th><th>Type</th><th>Answer</th><th>TTL</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if ($dns_records === null): ?>
                                <tr><td colspan="5" class="admin-empty"><?php echo htmlspecialchars($dns_error); ?></td></tr>
                            <?php elseif ($dns_records): foreach ($dns_records as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r->fqdn); ?></td>
                                    <td><?php echo htmlspecialchars($r->type); ?></td>
                                    <td class="num"><?php echo htmlspecialchars(isset($r->answer) ? $r->answer : ''); ?></td>
                                    <td class="num"><?php echo (int) (isset($r->ttl) ? $r->ttl : 0); ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <form method="post" action="/admin/dyndns" onsubmit="return confirm('Delete <?php echo htmlspecialchars($r->fqdn); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="delete_record">
                                                <input type="hidden" name="record_id" value="<?php echo (int) $r->id; ?>">
                                                <button class="btn-pill danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="admin-empty">No active dyndns records.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
footer();
