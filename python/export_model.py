"""Mengulang pipeline notebook DATA_MINING (Random Forest) lalu menulis file
yang dibaca Laravel (php artisan seruni:import).

Pemakaian:
    pip install pandas numpy scikit-learn openpyxl
    python export_model.py datapenjualan_CLEAN_FIX.xlsx ../storage/app/seruni

Keluaran di folder tujuan:
    data_penjualan.csv        semua baris data bersih (untuk halaman Eksplorasi)
    hasil_prediksi.csv        aktual + prediksi (Training/Validation/Testing) + proyeksi 7 hari
    metrik_model.json         metrik, perbandingan model, importance, kesesuaian arah
"""
import json, os, sys
import numpy as np, pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.inspection import permutation_importance
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

SRC = sys.argv[1] if len(sys.argv) > 1 else "datapenjualan_CLEAN_FIX.xlsx"
OUT = sys.argv[2] if len(sys.argv) > 2 else "../storage/app/seruni"
HARI_PROYEKSI = 7
os.makedirs(OUT, exist_ok=True)

HARI = {0: "Senin", 1: "Selasa", 2: "Rabu", 3: "Kamis", 4: "Jumat", 5: "Sabtu", 6: "Minggu"}
df = pd.read_excel(SRC, sheet_name="Data_Bersih")
df["Hari"] = df["Tanggal"].dt.dayofweek.map(HARI)
df.assign(Tanggal=df["Tanggal"].dt.strftime("%Y-%m-%d")).to_csv(f"{OUT}/data_penjualan.csv", index=False)

# ---- fitur (sama dengan notebook) ----
kk = ["Lokasi", "Menu Minuman"]
d = df.sort_values(kk + ["Tanggal"]).copy()
d["Terjual_Hari_Sebelumnya"] = d.groupby(kk)["Terjual"].shift(1)
d["Nomor_Hari"] = d["Tanggal"].dt.dayofweek
fit_num = ["Nomor_Hari", "Harga Satuan", "Terjual_Hari_Sebelumnya"]
fit_kat = kk
siap = d.dropna(subset=["Terjual_Hari_Sebelumnya"]).sort_values("Tanggal").copy()

# ---- split kronologis 70:15:15 berdasarkan tanggal ----
tgl = sorted(siap["Tanggal"].unique())
b1, b2 = int(len(tgl) * .70), int(len(tgl) * .85)
train = siap[siap["Tanggal"].isin(tgl[:b1])]
valid = siap[siap["Tanggal"].isin(tgl[b1:b2])]
test = siap[siap["Tanggal"].isin(tgl[b2:])]

def buat_model(**p):
    pre = ColumnTransformer([("kategori", OneHotEncoder(handle_unknown="ignore"), fit_kat),
                             ("numerik", "passthrough", fit_num)])
    return Pipeline([("preprocessor", pre), ("regressor", RandomForestRegressor(random_state=42, **p))])

def metrik(y, p):
    return dict(MAE=mean_absolute_error(y, p), RMSE=float(np.sqrt(mean_squared_error(y, p))), R2=r2_score(y, p))

# ---- pembandingan konfigurasi pada validation ----
konfigurasi = [dict(n_estimators=100, max_depth=None, min_samples_leaf=1),
               dict(n_estimators=100, max_depth=10, min_samples_leaf=2),
               dict(n_estimators=200, max_depth=10, min_samples_leaf=2),
               dict(n_estimators=200, max_depth=15, min_samples_leaf=3)]
optimasi = []
for i, p in enumerate(konfigurasi, 1):
    m = buat_model(**p).fit(train[fit_num + fit_kat], train["Terjual"])
    optimasi.append({"Konfigurasi": i, **{k: (None if v is None else v) for k, v in p.items()},
                     **metrik(valid["Terjual"], m.predict(valid[fit_num + fit_kat]))})

# ---- model terbaik = konfigurasi dengan MAE validasi terkecil ----
terbaik = min(optimasi, key=lambda o: o['MAE'])   # MAE validasi terkecil
param = {k: terbaik[k] for k in ('n_estimators', 'max_depth', 'min_samples_leaf')}
model = buat_model(**param).fit(train[fit_num + fit_kat], train["Terjual"])
hasil = []
for nama, g in [("Training", train), ("Validation", valid), ("Testing", test)]:
    h = g[["Tanggal", "Lokasi", "Menu Minuman", "Stok", "Sisa", "Harga Satuan", "Terjual", "Terjual_Hari_Sebelumnya"]].copy()
    h.columns = ["Tanggal", "Lokasi", "Menu", "Stok", "Sisa", "Harga", "Aktual", "Baseline"]
    h["Prediksi"] = model.predict(g[fit_num + fit_kat])
    h["Dataset"] = nama
    hasil.append(h)
hs = pd.concat(hasil, ignore_index=True).sort_values(["Lokasi", "Menu", "Tanggal"]).reset_index(drop=True)
hs["AE_RF"] = (hs["Aktual"] - hs["Prediksi"]).abs()
hs["AE_Lag"] = (hs["Aktual"] - hs["Baseline"]).abs()

# ---- pembanding sederhana ----
latih = hs[hs["Dataset"] == "Training"].copy()
latih["DOW"] = latih["Tanggal"].dt.dayofweek
m_kom = latih.groupby(["Lokasi", "Menu"])["Aktual"].mean().rename("Mean_Kom").reset_index()
m_dow = latih.groupby(["Lokasi", "Menu", "DOW"])["Aktual"].mean().rename("Mean_Kom_DOW").reset_index()
hs["DOW"] = hs["Tanggal"].dt.dayofweek
hs = hs.merge(m_kom, on=["Lokasi", "Menu"], how="left").merge(m_dow, on=["Lokasi", "Menu", "DOW"], how="left")
hs["Mean_Kom_DOW"] = hs["Mean_Kom_DOW"].fillna(hs["Mean_Kom"])

def arah(x):
    return np.where(x > 0, "Naik", np.where(x < 0, "Turun", "Tetap"))
hs["Arah_Aktual"] = arah(hs["Aktual"] - hs["Baseline"])
def arah_dari(kol):
    return arah(np.round(hs[kol]) - hs["Baseline"])

pembanding = []
for ds in ["Validation", "Testing"]:
    g = hs[hs["Dataset"] == ds]
    nt = g["Arah_Aktual"] != "Tetap"
    for nama, kol in [("Random Forest", "Prediksi"), ("Lag (penjualan sebelumnya)", "Baseline"),
                      ("Rata-rata per kombinasi", "Mean_Kom"), ("Rata-rata per kombinasi x hari", "Mean_Kom_DOW")]:
        e = g["Aktual"] - g[kol]
        ap = pd.Series(arah(np.round(g[kol]) - g["Baseline"]), index=g.index)
        pembanding.append({"Dataset": ds, "Model": nama, "MAE": float(e.abs().mean()),
                           "RMSE": float(np.sqrt((e ** 2).mean())),
                           "Arah_Sesuai": float((ap == g["Arah_Aktual"]).mean() * 100),
                           "Arah_NaikTurun": float((ap[nt] == g["Arah_Aktual"][nt]).mean() * 100)})

# ---- evaluasi akhir + metrik tambahan ----
akhir = []
for ds in ["Training", "Validation", "Testing"]:
    g = hs[hs["Dataset"] == ds]
    pos = g[g["Aktual"] > 0]
    akhir.append({"Dataset": ds, "n": int(len(g)), **metrik(g["Aktual"], g["Prediksi"]),
                  "WAPE": float(g["AE_RF"].sum() / g["Aktual"].sum() * 100),
                  "MAPE": float((pos["AE_RF"] / pos["Aktual"]).mean() * 100)})

# ---- importance (permutasi, pada validation + testing) ----
imp = []
for nama, g in [("Validation", valid), ("Testing", test)]:
    r = permutation_importance(model, g[fit_num + fit_kat], g["Terjual"], scoring="neg_mean_absolute_error",
                               n_repeats=30, random_state=42, n_jobs=-1)
    for f, v in zip(fit_num + fit_kat, r.importances_mean):
        imp.append({"Dataset": nama, "Fitur": f, "Naik_MAE": float(v)})

# ---- proyeksi rekursif ke depan ----
nama_fitur = {"Nomor_Hari": "Hari dalam minggu", "Harga Satuan": "Harga satuan",
              "Terjual_Hari_Sebelumnya": "Penjualan tercatat sebelumnya", "Lokasi": "Lokasi", "Menu Minuman": "Menu"}
akhir_tgl = df["Tanggal"].max()
proy = []
for (lok, mn), g in df.sort_values("Tanggal").groupby(kk):
    lag, harga = int(g["Terjual"].iloc[-1]), int(g["Harga Satuan"].iloc[-1])
    stok_ref = float(g["Stok"].tail(7).mean())
    for i in range(1, HARI_PROYEKSI + 1):
        t = akhir_tgl + pd.Timedelta(days=i)
        x = pd.DataFrame([{"Nomor_Hari": t.dayofweek, "Harga Satuan": harga, "Terjual_Hari_Sebelumnya": lag,
                           "Lokasi": lok, "Menu Minuman": mn}])
        p = float(max(model.predict(x)[0], 0))
        proy.append({"Tanggal": t, "Lokasi": lok, "Menu": mn, "Stok": round(stok_ref, 1), "Sisa": None,
                     "Harga": harga, "Aktual": None, "Baseline": lag, "Prediksi": p, "Dataset": "Proyeksi"})
        lag = p
semua = pd.concat([hs[["Tanggal", "Lokasi", "Menu", "Stok", "Sisa", "Harga", "Aktual", "Baseline", "Prediksi", "Dataset"]],
                   pd.DataFrame(proy)], ignore_index=True)
semua["Tanggal"] = pd.to_datetime(semua["Tanggal"]).dt.strftime("%Y-%m-%d")
semua["Prediksi"] = semua["Prediksi"].round(3)
semua.to_csv(f"{OUT}/hasil_prediksi.csv", index=False)

info = {
    "periode": [str(df["Tanggal"].min().date()), str(df["Tanggal"].max().date())],
    "split": {n: [str(pd.Timestamp(g["Tanggal"].min()).date()), str(pd.Timestamp(g["Tanggal"].max()).date()), int(len(g))]
              for n, g in [("Training", train), ("Validation", valid), ("Testing", test)]},
    "proyeksi": [str((akhir_tgl + pd.Timedelta(days=1)).date()), str((akhir_tgl + pd.Timedelta(days=HARI_PROYEKSI)).date())],
    "model": "RandomForestRegressor(n_estimators=%s, max_depth=%s, min_samples_leaf=%s, random_state=42)" % (param["n_estimators"], param["max_depth"], param["min_samples_leaf"]),
    "fitur": [nama_fitur[f] for f in fit_num + fit_kat],
    "evaluasi_akhir": akhir, "pembanding": pembanding, "optimasi": optimasi, "importance": imp,
}
json.dump(info, open(f"{OUT}/metrik_model.json", "w", encoding="utf-8"), indent=1, default=str)
print("Selesai ->", OUT)
for a in akhir: print({k: (round(v, 3) if isinstance(v, float) else v) for k, v in a.items()})
for p in pembanding: print({k: (round(v, 2) if isinstance(v, float) else v) for k, v in p.items()})
