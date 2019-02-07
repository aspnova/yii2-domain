<?php

namespace aspnova\domain\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


/**
 * DomainSearch represents the model behind the search form about `app\modules\domain\models\Domain`.
 */
class DomainSearch extends Domain
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'type_domain', 'publish'], 'integer'],
            [['name', 'date_publish_start','alias'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {

        $query = Domain::find();
            //->select([ 'domain.*' ,
      //      'count(distinct comments.id) as count_domains',
     //       'count(distinct scripts.id) as count_scripts',
         //   'count(distinct url_domain.id) as count_url'])->
      //  leftJoin('comments','domain.id = comments.domain_id')->
      //  leftJoin('scripts','domain.id = scripts.domain_id')->
     //   leftJoin('url_domain','domain.id = url_domain.domain_id')->
      //  groupBy('domain.id');//->orderBy(['blog.date_post'=>SORT_DESC , 'blog.id'=>SORT_DESC]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date_publish_start'=>SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([

            'type_domain' => $this->type_domain,
            'publish' => $this->publish,
            'date_publish_start' => $this->date_publish_start,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'alias', $this->alias]);

        return $dataProvider;
    }
}
